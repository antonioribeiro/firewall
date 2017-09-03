<?php

namespace PragmaRX\Firewall\Support;

use Carbon\Carbon;
use PragmaRX\Firewall\Events\AttackDetected;
use PragmaRX\Firewall\Firewall;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;

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
    protected $record = [
        'ip' => null,

        'country' => null,
    ];

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
     * The country.
     *
     * @var string
     */
    protected $country;

    /**
     * The enabled items.
     *
     * @var array
     */
    protected $enabledItems;

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

        $this->loadConfig();
    }

    /**
     * Blacklist the IP address.
     *
     * @param $record
     *
     * @return bool
     */
    protected function blacklist($record)
    {
        if ($record['isBlacklisted']) {
            return false;
        }

        $blacklistUnknown = $this->config->get("attack_blocker.action.{$record['type']}.blacklist_unknown");

        $blackWhitelisted = $this->config->get("attack_blocker.action.{$record['type']}.blacklist_whitelisted");

        if ($blacklistUnknown || $blackWhitelisted) {
            $record['isBlacklisted'] = true;

            $ipAddress = $record['type'] == 'country'
                        ? 'country:'.$record['country_code']
                        : $record['ipAddress'];

            $this->firewall->blacklist($ipAddress, $blackWhitelisted);

            $this->save($record);
        }
    }

    /**
     * Check for expiration.
     *
     * @return mixed
     */
    protected function checkExpiration()
    {
        $this->enabledItems->each(function ($index, $type) {
            if (($this->record[$type]['lastRequestAt']->diffInSeconds(Carbon::now())) <= ($this->getMaxSecondsForType($type))) {
                return $this->record;
            }

            return $this->getEmptyRecord($this->record[$type]['key'], $type);
        });
    }

    /**
     * Get an empty record.
     *
     * @return array
     */
    protected function getEmptyRecord($key, $type)
    {
        return $this->makeRecord($key, $type);
    }

    /**
     * Get a timestamp for the time the cache should expire.
     *
     * @param $type
     *
     * @return float|int
     */
    protected function getExpirationTimestamp($type)
    {
        return Carbon::now()->addSeconds($this->getMaxSecondsForType($type));
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
     * @param $ipAddress
     *
     * @return array|null|void
     */
    protected function getGeo($ipAddress)
    {
        return $this->firewall->getGeoIp()->searchAddr($ipAddress);
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
     * @param string $type
     * @return int|mixed
     */
    protected function getMaxRequestCountForType($type = 'ip')
    {
        return !is_null($this->maxRequestCount)
            ? $this->maxRequestCount
            : ($this->maxRequestCount = $this->config->get("attack_blocker.allowed_frequency.{$type}.requests"));
    }

    /**
     * Get max seconds from config.
     *
     * @param $type
     *
     * @return mixed
     */
    protected function getMaxSecondsForType($type)
    {
        return !is_null($this->maxSeconds)
            ? $this->maxSeconds
            : ($this->maxSeconds = $this->config->get("attack_blocker.allowed_frequency.{$type}.seconds"));
    }

    /**
     * Get attack records.
     *
     * @return array
     */
    public function getRecord()
    {
        return $this->record;
    }

    /**
     * Get the response configuration.
     *
     * @return mixed
     */
    protected function getResponseConfig()
    {
        return $this->config->get('attack_blocker.response');
    }

    /**
     * Increment request count.
     *
     * @return mixed
     */
    protected function increment()
    {
        $this->enabledItems->each(function ($index, $type) {
            $this->save($type, ['requestCount' => $this->record[$type]['requestCount'] + 1]);
        });
    }

    /**
     * Check if this is an attack.
     *
     * @return mixed
     */
    protected function isAttack()
    {
        return $this->enabledItems->filter(function ($index, $type) {
            if (!$this->isWhitelisted($type) && $isAttack = $this->record[$type]['requestCount'] > $this->getMaxRequestCountForType($type)) {
                // $this->takeAction($this->record[$type]);

                return true;
            }
        })->count() > 0;
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

        return $this->isAttack();
    }

    /**
     * Get enabled state.
     */
    protected function isEnabled()
    {
        return count($this->enabledItems) > 0;
    }

    /**
     * Is the current user whitelisted?
     *
     * @param $type
     *
     * @return bool
     */
    private function isWhitelisted($type)
    {
        return $this->firewall->whichList($this->record[$type]['ipAddress']) == 'whitelist' &&
                $this->config->get("attack_blocker.action.{$this->record[$type]['type']}.blacklist_whitelisted");
    }

    /**
     * Load the configuration.
     */
    private function loadConfig()
    {
        $this->enabledItems = collect($this->config->get('attack_blocker.enabled'))->filter(function ($item) {
            return $item === true;
        });
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
        $this->ipAddress = $ipAddress;

        $this->loadRecordItems();

        $this->checkExpiration();

        $this->increment();
    }

    /**
     * Load all record items.
     */
    protected function loadRecordItems()
    {
        $this->enabledItems->each(function ($index, $type) {
            if (is_null($this->record[$type] = $this->cache->get($key = $this->makeKeyForType($type, $this->ipAddress)))) {
                $this->record[$type] = $this->getEmptyRecord($key, $type);
            }
        });
    }

    /**
     * Write to the log.
     *
     * @param $string
     */
    protected function log($string)
    {
        $this->firewall->log($string);
    }

    /**
     * Send attack the the log.
     *
     * @param $record
     */
    protected function logAttack($record)
    {
        $this->log("Attacker detected - IP: {$record['ipAddress']} - Request count: {$record['requestCount']}");
    }

    /**
     * Make a response.
     *
     */
    public function responseToAttack()
    {
        if ($this->isAttack()) {
            return (new Responder())->respond($this->getResponseConfig(), $this->record);
        }

        return null;
    }

    /**
     * Make a hashed key.
     *
     * @param $field
     *
     * @return string
     */
    public function makeHashedKey($field)
    {
        return hash(
            'sha256',
            $this->config->get('attack_blocker.cache_key_prefix').'-'.$field
        );
    }

    /**
     * Make the cache key to record countries.
     *
     * @param $ipAddress
     *
     * @return string
     */
    protected function makeKeyForType($type, $ipAddress)
    {
        if ($type == 'country') {
            $geo = $this->getGeo($ipAddress);

            if (is_null($geo)) {
                $this->log("No GeoIp info for {$ipAddress}, is it installed?");
            }

            if (!is_null($geo) && $this->country = $geo['country_code']) {
                return $this->makeHashedKey($this->country);
            }

            unset($this->enabledItems['country']);

            return;
        }

        return $this->makeHashedKey($this->ipAddress = $ipAddress);
    }

    /**
     * Make a record.
     *
     * @param $key
     * @param $type
     *
     * @return array
     */
    protected function makeRecord($key, $type)
    {
        $geo = $this->getGeo($this->ipAddress);

        return [
            'type' => $type,

            'key' => $key,

            'ipAddress' => $this->ipAddress,

            'requestCount' => 0,

            'firstRequestAt' => Carbon::now(),

            'lastRequestAt' => Carbon::now(),

            'isBlacklisted' => false,

            'wasNotified' => false,

            'userAgent' => request()->server('HTTP_USER_AGENT'),

            'server' => request()->server(),

            'geoIp' => $geo,

            'country_name' => $geo ? $geo['country_name'] : null,

            'country_code' => $geo ? $geo['country_code'] : null,

            'host' => gethostbyaddr($this->ipAddress),
        ];
    }

    /**
     * Send notifications.
     *
     * @param $record
     */
    protected function notify($record)
    {
        if (!$record['wasNotified'] && $this->config->get('notifications.enabled')) {
            $this->save($record['type'], ['wasNotified' => true]);

            collect($this->config->get('notifications.channels'))->filter(function ($value, $channel) use ($record) {
                try {
                    event(new AttackDetected($record, $channel));
                } catch (\Exception $exception) {
                    info($exception);
                }
            });
        }
    }

    /**
     * Renew first request timestamp, to keep the offender blocked.
     *
     * @param $record
     */
    protected function renew($record)
    {
        $this->save($record['type'], ['lastRequestAt' => Carbon::now()]);
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
     * @param $type
     * @param array $items
     *
     * @return array
     */
    protected function save($type, $items = [])
    {
        if (is_array($type)) {
            $items = $type;

            $type = $type['type'];
        }

        $this->record[$type] = array_merge($this->record[$type], $items);

        $this->cache->put($this->record[$type]['key'], $this->record[$type], $this->getExpirationTimestamp($type));

        return $this->record[$type];
    }

    /**
     * Take the necessary action to keep the offender blocked.
     */
    protected function takeAction($record)
    {
        $this->renew($record);

        $this->blacklist($record);

        $this->notify($record);

        $this->logAttack($record);
    }
}
