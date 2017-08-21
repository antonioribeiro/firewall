<?php

namespace PragmaRX\Firewall\Support;

use Carbon\Carbon;
use PragmaRX\Firewall\Events\AttackDetected;
use PragmaRX\Firewall\Firewall;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;
use PragmaRX\Support\GeoIp\GeoIp;

class AttackBlocker
{
    /**
     * The config.
     *
     * @var Config
     */
    protected $config;

    /**
     * The request record.
     *
     * @var Config
     */
    protected $record;

    /**
     * The cache.
     *
     * @var CacheManager
     */
    protected $cache;

    /**
     * The ip address.
     *
     * @var string
     */
    protected $ipAddress;

    /**
     * The cache key.
     *
     * @var string
     */
    protected $key;

    /**
     * The max request count.
     *
     * @var int
     */
    protected $maxRequestCount;

    /**
     * The max request count.
     *
     * @var int
     */
    protected $maxSeconds;

    /**
     * The firewall instance.
     *
     * @var Firewall
     */
    protected $firewall;

    /**
     * AttackBlocker constructor.
     *
     * @param Config       $config
     * @param CacheManager $cache
     */
    public function __construct(Config $config, CacheManager $cache)
    {
        $this->config = $config;

        $this->cache = $cache;
    }

    /**
     * Blacklist the IP address.
     */
    protected function blacklist()
    {
        if ($this->record['isBlacklisted']) {
            return false;
        }

        $blacklistUnknown = $this->config->get('attack_blocker.action.blacklist_unknown_ips');

        $blackWhitelisted = $this->config->get('attack_blocker.action.blacklist_whitelisted_ips');

        if ($blacklistUnknown || $blackWhitelisted) {
            $this->record['isBlacklisted'] = true;

            $this->firewall->blacklist($this->record['ipAddress'], $blackWhitelisted);
        }
    }

    /**
     * Check for expiration.
     *
     * @return mixed
     */
    protected function checkExpiration()
    {
        if (($this->record['lastRequestAt']->diffInSeconds(Carbon::now())) <= ($this->getMaxSeconds())) {
            return $this->record;
        }

        return $this->getEmptyRecord();
    }

    /**
     * Get an empty record.
     *
     * @return array
     */
    protected function getEmptyRecord()
    {
        return $this->makeRecord();
    }

    /**
     * Get firewall.
     *
     * @return Firewall
     */
    public function getFirewall()
    {
        return $this->firewall;
    }

    /**
     * Get the cache key.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Get max request count from config.
     */
    protected function getMaxRequestCount()
    {
        return !is_null($this->maxRequestCount)
            ? $this->maxRequestCount
            : ($this->maxRequestCount = $this->config->get('attack_blocker.allowed_frequency.requests'));
    }

    /**
     * Get max seconds from config.
     *
     * @return mixed
     */
    protected function getMaxSeconds()
    {
        return !is_null($this->maxSeconds)
            ? $this->maxSeconds
            : ($this->maxSeconds = $this->config->get('attack_blocker.allowed_frequency.seconds'));
    }

    /**
     * @return mixed
     */
    protected function getResponseConfig()
    {
        return $this->config->get('attack_blocker.response');
    }

    /**
     * Increment request count.
     *
     * @param $this->record
     *
     * @return mixed
     */
    protected function increment()
    {
        return $this->save(['requestCount' => $this->record['requestCount'] + 1]);
    }

    /**
     * Check if this is an attack.
     *
     * @return mixed
     */
    protected function isAttack()
    {
        if ($isAttack = $this->record['requestCount'] > $this->getMaxRequestCount()) {
            $this->takeAction($this->record);
        }

        return $isAttack;
    }

    /**
     * Check for attacks.
     *
     * @param $ipAddress
     *
     * @return mixed
     */
    public function isBeingAttacked($ipAddress)
    {
        if (!$this->isEnabled()) {
            return false;
        }

        $this->loadRecord($ipAddress);

        if ($this->isAttack()) {
            return $this->makeAttackResponse();
        }

        return false;
    }

    /**
     * Get enabled state.
     */
    protected function isEnabled()
    {
        return $this->config->get('attack_blocker.enabled');
    }

    /**
     * Load a record.
     *
     * @param $ipAddress
     *
     * @return array|\Illuminate\Contracts\Cache\Repository
     */
    protected function loadRecord($ipAddress)
    {
        if (is_null($this->record = $this->cache->get($this->makeKey($ipAddress)))) {
            $this->record = $this->getEmptyRecord();
        }

        $this->checkExpiration();

        $this->increment();

        return $this->record;
    }

    protected function log()
    {
        $this->firewall->log("Attacker detected - IP: {$this->record['ipAddress']} - Request count: {$this->record['requestCount']}");
    }

    protected function makeAttackResponse()
    {
        return (new Responder())->respond($this->getResponseConfig(), $this->record);
    }

    /**
     * Make the cache key.
     *
     * @param $ipAddress
     *
     * @return string
     */
    protected function makeKey($ipAddress)
    {
        return
            $this->key =
                $this->config->get('attack_blocker.cache_key_prefix').
                '-'.
                ($this->ipAddress = $ipAddress);
    }

    /**
     * Make a record.
     *
     * @param null $ipAddress
     * @param null $requestCount
     * @param null $lastRequestAt
     *
     * @return array
     */
    protected function makeRecord($ipAddress = null, $requestCount = null, $lastRequestAt = null)
    {
        return [
            'ipAddress' => $ipAddress ?: $this->ipAddress,

            'requestCount' => $requestCount ?: 0,

            'firstRequestAt' => $lastRequestAt ?: Carbon::now(),

            'lastRequestAt' => $lastRequestAt ?: Carbon::now(),

            'isBlacklisted' => false,

            'wasNotified' => false,

            'userAgent' => request()->server('HTTP_USER_AGENT'),

            'server' => request()->server(),

            'geoIp' => $this->firewall->geoIp->searchAddr('8.8.8.8'),
        ];
    }

    protected function notify()
    {
        if (!$this->record['wasNotified'] && $this->config->get('notifications.enabled')) {
            $this->save(['wasNotified' => true]);

            collect($this->config->get('notifications.channels'))->filter(function ($value, $channel) {
                try {
                    event(new AttackDetected($this->record, $channel));
                } catch (\Exception $exception) {
                    dd($exception);
                    // Notifications are broken, ignore it
                }
            });
        }
    }

    /**
     * Renew first request timestamp, to keep the offender blocked.
     */
    protected function renew()
    {
        $this->save(['lastRequestAt' => Carbon::now()]);
    }

    /**
     * Set firewall.
     *
     * @param Firewall $firewall
     */
    public function setFirewall($firewall)
    {
        $this->firewall = $firewall;
    }

    /**
     * Store record on cache.
     *
     * @param array $items
     *
     * @return mixed
     */
    protected function save($items = [])
    {
        $this->record = array_merge($this->record, $items);

        $this->cache->put($this->getKey(), $this->record, $this->getMaxSeconds() / 60);

        return $this->record;
    }

    /**
     * Take the necessary action to keep the offender blocked.
     */
    protected function takeAction()
    {
        $this->log();

        $this->notify();

        $this->renew();

        $this->blacklist();
    }
}
