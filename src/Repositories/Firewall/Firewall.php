<?php

namespace PragmaRX\Firewall\Repositories\Firewall;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Filesystem\Filesystem;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;
use PragmaRX\Support\IpAddress;
use ReflectionClass;

class Firewall implements FirewallInterface
{
    const CACHE_BASE_NAME = 'firewall.';

    const IP_ADDRESS_LIST_CACHE_NAME = 'firewall.ip_address_list';

    /**
     * @var object
     */
    private $model;

    /**
     * @var Cache|CacheManager
     */
    private $cache;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Filesystem
     */
    private $fileSystem;

    /**
     * Create an instance of Message.
     *
     * @param object             $model
     * @param Cache|CacheManager $cache
     * @param Config             $config
     * @param Filesystem         $fileSystem
     */
    public function __construct($model, CacheManager $cache, Config $config, Filesystem $fileSystem)
    {
        $this->model = $model;

        $this->cache = $cache;

        $this->config = $config;

        $this->fileSystem = $fileSystem;
    }

    /**
     * Add ip or range to array list.
     *
     * @param $whitelist
     * @param $ip
     */
    private function addToArrayList($whitelist, $ip)
    {
        $data = $this->config->set($list = $whitelist ? 'whitelist' : 'blacklist', $ip);

        $data[] = $ip;

        $this->config->set($list, $data);
    }

    /**
     * Add ip or range to database.
     *
     * @param $whitelist
     * @param $ip
     *
     * @return mixed
     */
    private function addToDatabaseList($whitelist, $ip)
    {
        $this->model->unguard();

        $model = $this->model->create([
            'ip_address'  => $ip,
            'whitelisted' => $whitelist,
        ]);

        $this->cacheRemember($model);

        return $model;
    }

    /**
     * @param $model
     */
    private function addToSession($model)
    {
        $current = $this->getSessionIps();

        $current = $current->push($model)->unique('ip_address');

        $this->getSession()->put('pragmarx.firewall', $current);

        return $model;
    }

    /**
     * @param $whitelist
     * @param $ip
     *
     * @return object
     */
    private function createModel($whitelist, $ip)
    {
        $class = new ReflectionClass(get_class($this->model));

        $model = $class->newInstanceArgs([
                                             [
                                                 'ip_address'  => $ip,
                                                 'whitelisted' => $whitelist,
                                             ],
                                         ]);

        return $model;
    }

    private function expandIpAddresses($all)
    {
        $result = [];

        foreach ($all as $ip) {
        }

        return collect($result);
    }

    /**
     * Find a Ip in the data source.
     *
     * @param string $ip
     *
     * @return object|null
     */
    public function find($ip)
    {
        if ($this->cacheHas($ip)) {
            return $this->cacheGet($ip);
        }

        if ($model = $this->findIp($ip)) {
            $this->cacheRemember($model);
        }

        return $model;
    }

    /**
     * Find a Ip in the data source.
     *
     * @param string $ip
     *
     * @return object|null
     */
    public function addToList($whitelist, $ip)
    {
        if ($this->config->get('use_database')) {
            return $this->addToDatabaseList($whitelist, $ip);
        }

        return $this->addToArrayList($whitelist, $ip);
    }

    /**
     * Find a Ip in the data source.
     *
     * @param string $ip
     *
     * @return object|null
     */
    public function addToSessionList($whitelist, $ip)
    {
        $this->removeFromSession($model = $this->createModel($whitelist, $ip));

        return $this->addToSession($model);
    }

    public function delete($ipAddress)
    {
        if ($ip = $this->find($ipAddress)) {
            $ip->delete();

            $this->cacheForget($ipAddress);

            return true;
        }

        return false;
    }

    public function cacheKey($ip)
    {
        return static::CACHE_BASE_NAME."ip_address.$ip";
    }

    public function cacheHas($ip)
    {
        if ($this->config->get('cache_expire_time')) {
            return $this->cache->has($this->cacheKey($ip));
        }

        return false;
    }

    public function cacheGet($ip)
    {
        return $this->cache->get($this->cacheKey($ip));
    }

    public function cacheForget($ip)
    {
        $this->cache->forget($this->cacheKey($ip));
    }

    public function cacheRemember($model)
    {
        if ($timeout = $this->config->get('cache_expire_time')) {
            $this->cache->put($this->cacheKey($model->ip_address), $model, $timeout);
        }
    }

    public function all()
    {
        $cacheTime = $this->config->get('ip_list_cache_expire_time');

        if ($cacheTime && $list = $this->cache->get(static::IP_ADDRESS_LIST_CACHE_NAME)) {
            return $list;
        }

        $list = $this->mergeLists(
            $this->getAllFromDatabase(),
            $this->toModels($this->getNonDatabaseIps()),
            $this->getSessionIps()
        );

        if ($cacheTime) {
            $this->cache->put(
                static::IP_ADDRESS_LIST_CACHE_NAME,
                $list,
                $this->config->get('ip_list_cache_expire_time')
            );
        }

        return $list;
    }

    public function clear()
    {
        /**
         * Deletes one by one to also remove them from cache.
         */
        $deleted = 0;

        foreach ($this->all() as $ip) {
            if ($this->delete($ip['ip_address'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    private function findIp($ip)
    {
        if ($model = $this->nonDatabaseFind($ip)) {
            return $model;
        }

        if ($this->config->get('use_database')) {
            return $this->model->where('ip_address', $ip)->first();
        }
    }

    /**
     * @return \Illuminate\Foundation\Application|mixed
     */
    private function getSession()
    {
        return app($this->config->get('session_binding'));
    }

    private function getSessionIps()
    {
        return collect($this->getSession()->get('pragmarx.firewall', []));
    }

    private function nonDatabaseFind($ip)
    {
        $ips = $this->getNonDatabaseIps();

        if ($ip = $this->ipArraySearch($ip, $ips)) {
            return $this->makeModel($ip);
        }

        $ips = $this->getSessionIps()->toArray();

        if ($ip = $this->ipArraySearch($ip, $ips)) {
            return $this->makeModel($ip);
        }
    }

    private function getNonDatabaseIps()
    {
        return array_merge_recursive(
            array_map(function ($ip) {
                $ip['whitelisted'] = true;

                return $ip;
            }, $this->formatIpArray($this->config->get('whitelist'))),

            array_map(function ($ip) {
                $ip['whitelisted'] = false;

                return $ip;
            }, $this->formatIpArray($this->config->get('blacklist')))
        );
    }

    private function removeFromSession($ip)
    {
        $current = $this->getSessionIps();

        $current = $current->filter(function ($model) use ($ip) {
            return $model->ip_address !== $ip->ip_address;
        });

        $this->getSession()->put('pragmarx.firewall', $current);
    }

    /**
     * Find a Ip in the data source.
     *
     * @param string $ip
     *
     * @return object|null
     */
    public function removeFromSessionList($ip)
    {
        $this->removeFromSession($this->createModel(false, $ip));
    }

    private function toModels($ipList)
    {
        $ips = [];

        foreach ($ipList as $ip) {
            $ips[] = $this->makeModel($ip);
        }

        return $ips;
    }

    /**
     * @param $ip
     *
     * @return mixed
     */
    private function makeModel($ip)
    {
        return $this->model->newInstance($ip);
    }

    private function readFile($file)
    {
        if ($this->fileSystem->exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            return $this->makeArrayOfIps($lines);
        }

        return [];
    }

    private function toCollection($array)
    {
        return new Collection($array);
    }

    private function formatIpArray($list)
    {
        return array_map(function ($ip) {
            return ['ip_address' => $ip];
        }, $this->makeArrayOfIps($list));
    }

    private function makeArrayOfIps($list)
    {
        $list = $list ?: [];

        $ips = [];

        foreach ($list as $item) {
            $ips = array_merge($ips, $this->getIpsFromAnything($item));
        }

        return $ips;
    }

    private function getIpsFromAnything($item)
    {
        if (starts_with($item, 'country:')) {
            return [$item];
        }

        $item = $this->hostToIp($item);

        if (IpAddress::ipV4Valid($item)) {
            return [$item];
        }

        return $this->readFile($item);
    }

    private function ipArraySearch($ip, $ips)
    {
        foreach ($ips as $key => $value) {
            if (
                (isset($value['ip_address']) && $value['ip_address'] == $ip) ||
                (strval($key) == $ip) ||
                ($value == $ip)
            ) {
                return $value;
            }
        }

        return false;
    }

    /**
     * Get all IPs from database.
     *
     * @return array
     */
    private function getAllFromDatabase()
    {
        if ($this->config->get('use_database')) {
            return $this->model->all();
        } else {
            return $this->toCollection([]);
        }
    }

    private function mergeLists($database_ips, $config_ips, $session_ips = [])
    {
        return collect($database_ips)
                ->merge(collect($config_ips))
                ->merge(collect($session_ips));
    }

    public function hostToIp($ip)
    {
        if (is_string($ip) && starts_with($ip, $string = 'host:')) {
            return gethostbyname(str_replace($string, '', $ip));
        }

        return $ip;
    }
}
