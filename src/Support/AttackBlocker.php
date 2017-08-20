<?php

namespace PragmaRX\Firewall\Support;

use Carbon\Carbon;
use PragmaRX\Firewall\Firewall;
use PragmaRX\Support\Config;
use PragmaRX\Support\CacheManager;

class AttackBlocker
{
    /**
     * The config.
     *
     * @var Config
     */
    private $config;

    /**
     * The cache.
     *
     * @var CacheManager
     */
    private $cache;

    /**
     * The ip address.
     *
     * @var string
     */
    private $ipAddress;

    /**
     * The cache key.
     *
     * @var string
     */
    private $key;

    /**
     * The max request count.
     *
     * @var integer
     */
    private $maxRequestCount;

    /**
     * The max request count.
     *
     * @var integer
     */
    private $maxSeconds;

    /**
     * The firewall instance.
     *
     * @var Firewall
     */
    private $firewall;

    /**
     * AttackBlocker constructor.
     *
     * @param Config $config
     * @param CacheManager $cache
     */
    public function __construct(Config $config, CacheManager $cache)
    {
        $this->config = $config;

        $this->cache = $cache;
    }

    /**
     * Blacklist the IP address.
     *
     * @param $record
     */
    private function blacklist($record)
    {
        $blacklistUnkown = $this->config->get('attack_blocker.action.blacklist_unknown_ips');

        $blackWhitelisted = $this->config->get('attack_blocker.action.blacklist_whitelisted_ips');

        if ($blacklistUnkown || $blackWhitelisted) {
            $this->firewall->blacklist($record['ipAddress'], $blackWhitelisted);
        }
    }

    /**
     * Check for expiration.
     *
     * @param $record
     * @return mixed
     */
    private function checkExpiration($record)
    {
        if (($record['firstRequestAt']->diffInSeconds(Carbon::now())) <= ($this->getMaxSeconds())) {
            return $record;
        }

        return $this->getEmptyRecord();
    }

    /**
     * Get an empty record.
     *
     * @return array
     */
    private function getEmptyRecord()
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
     *
     */
    private function getMaxRequestCount()
    {
        return ! is_null($this->maxRequestCount)
            ? $this->maxRequestCount
            : ($this->maxRequestCount = $this->config->get('attack_blocker.allowed_frequency.requests'))
        ;
    }

    /**
     * Get max seconds from config.
     *
     * @return mixed
     */
    private function getMaxSeconds()
    {
        return ! is_null($this->maxSeconds)
            ? $this->maxSeconds
            : ($this->maxSeconds = $this->config->get('attack_blocker.allowed_frequency.seconds'))
        ;
    }

    /**
     *
     * @return mixed
     */
    private function getResponseConfig()
    {
        return $this->config->get('attack_blocker.response');
    }

    /**
     * Increment request count.
     *
     * @param $record
     * @return mixed
     */
    private function increment($record)
    {
        $record['requestCount'] = $record['requestCount'] + 1;

        return $this->store($record);
    }

    /**
     * Check if this is an attack.
     *
     * @param $record
     * @return mixed
     */
    private function isAttack($record)
    {
        if ($isAttack = $record['requestCount'] > $this->getMaxRequestCount()) {
            $this->takeAction($record);
        }

        return $isAttack;
    }

    /**
     * Check for attacks.
     *
     * @param $ipAddress
     * @return mixed
     */
    public function isBeingAttacked($ipAddress)
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $record = $this->increment(
            $this->checkExpiration(
                $this->loadRecord($ipAddress)
            )
        );

        if ($this->isAttack($record)) {
            return $this->makeAttackResponse($record);
        }

        return false;
    }

    /**
     * Get enabled state.
     *
     */
    private function isEnabled()
    {
        return $this->config->get('attack_blocker.enabled');
    }

    /**
     * Load a record.
     *
     * @param $ipAddress
     * @return array|\Illuminate\Contracts\Cache\Repository
     */
    private function loadRecord($ipAddress)
    {
        if (is_null($record = $this->cache->get($this->makeKey($ipAddress)))) {
            return $this->getEmptyRecord();
        }

        return $record;
    }

    private function log($record)
    {
        $this->firewall->log("Attacker detected - IP: {$record['ipAddress']} - Request count: {$record['requestCount']}");
    }

    private function makeAttackResponse($record)
    {
        return (new Responder())->respond($this->getResponseConfig(), $record);
    }

    /**
     * Make the cache key.
     *
     * @param $ipAddress
     * @return string
     */
    private function makeKey($ipAddress)
    {
        return
            $this->key =
                $this->config->get('attack_blocker.cache_key_prefix') .
                '-' .
                ($this->ipAddress = $ipAddress)
        ;
    }

    /**
     * Make a record.
     *
     * @param null $ipAddress
     * @param null $requestCount
     * @param null $firstRequestAt
     * @return array
     */
    private function makeRecord($ipAddress = null, $requestCount = null, $firstRequestAt = null)
    {
        return [
            'ipAddress' => $ipAddress ?: $this->ipAddress,

            'requestCount' => $requestCount ?: 0,

            'firstRequestAt' => $firstRequestAt ?: Carbon::now(),
        ];
    }

    private function notify($record)
    {

    }

    /**
     * Renew first request timestamp, to keep the offender blocked.
     *
     * @param $record
     */
    private function renew($record)
    {
        $record['firstRequestAt'] = Carbon::now();

        $this->store($record);
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
     * @param $record
     * @return mixed
     */
    private function store($record)
    {
        $this->cache->put($this->getKey(), $record, $this->getMaxSeconds() / 60);

        return $record;
    }

    /**
     * Take the necessary action to keep the offender blocked.
     *
     * @param $record
     */
    private function takeAction($record)
    {
        $this->log($record);

        $this->notify($record);

        $this->renew($record);

        $this->blacklist($record);
    }
}
