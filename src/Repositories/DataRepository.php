<?php

namespace PragmaRX\Firewall\Repositories;

/*
 * Part of the Firewall package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Firewall
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

use ReflectionClass;
use PragmaRX\Support\Config;
use PragmaRX\Support\IpAddress;
use PragmaRX\Support\Filesystem;
use PragmaRX\Support\CacheManager;
use Illuminate\Database\Eloquent\Collection;
use PragmaRX\Firewall\Vendor\Laravel\Models\Firewall;

class DataRepository implements DataRepositoryInterface
{
    const CACHE_BASE_NAME = 'firewall.';

    const IP_ADDRESS_LIST_CACHE_NAME = 'firewall.ip_address_list';

    /**
     * @var object
     */
    public $firewall;

    /**
     * @var object
     */
    public $countries;

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
     * @var Message
     */
    private $messageRepository;

    /**
     * Create instance of DataRepository.
     *
     * @param Firewall $model
     * @param Config $config
     * @param CacheManager $cache
     * @param Filesystem $fileSystem
     * @param Countries $countries
     */
    public function __construct(
        Firewall $model,
        Config $config,
        CacheManager $cache,
        Filesystem $fileSystem,
        Countries $countries,
        Message $messageRepository
    ) {
        $this->model = $model;

        $this->config = $config;

        $this->fileSystem = $fileSystem;

        $this->cache = $cache;

        $this->countries = $countries;

        $this->messageRepository = $messageRepository;
    }

    /**
     * Add ip or range to array list.
     *
     * @param $whitelist
     * @param $ip
     */
    private function addToArrayList($whitelist, $ip)
    {
        $data = $this->config->get($list = $whitelist ? 'whitelist' : 'blacklist');

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

    /**
     * Find an IP address in the data source.
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
     * Find an IP address by country.
     *
     * @param $country
     *
     * @return bool|null|object
     */
    public function findByCountry($country)
    {
        if ($this->config->get('enable_country_search') && !is_null($country = $this->makeCountryFromString($country))) {
            return $this->find($country);
        }

        return false;
    }

    /**
     * Make a country info from a string.
     *
     * @param $country
     * @return bool|string
     */
    public function makeCountryFromString($country)
    {
        if ($ips = IpAddress::isCidr($country)) {
            $country = $ips[0];
        }

        if ($this->validCountry($country)) {
            return $country;
        }

        if ($this->ipIsValid($country)) {
            $country = $this->countries->getCountryFromIp($country);
        }

        return "country:{$country}";
    }

    /**
     * Get country code from an IP address.
     *
     * @param $ip_address
     *
     * @return bool|string
     */
    public function getCountryFromIp($ip_address)
    {
        return $this->countries->getCountryFromIp($ip_address);
    }

    /**
     * Check if IP address is valid.
     *
     * @param $ip
     *
     * @return bool
     */
    public function ipIsValid($ip)
    {
        $ip = $this->hostToIp($ip);

        try {
            return IpAddress::ipV4Valid($ip) || $this->validCountry($ip);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Find a Ip in the data source.
     *
     * @param string $ip
     *
     * @return object|null
     */
    public function addToProperList($whitelist, $ip)
    {
        if ($this->config->get('use_database')) {
            return $this->addToDatabaseList($whitelist, $ip);
        }

        return $this->addToArrayList($whitelist, $ip);
    }

    public function delete($ipAddress)
    {
        if ($this->config->get('use_database')) {
            return $this->removeFromDatabaseList($ipAddress);
        }

        return $this->removeFromArrayList($ipAddress);
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
            $this->toModels($this->getNonDatabaseIps())
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

    /**
     * Get all IP addresses by country.
     *
     * @param $country
     * @return static
     */
    public function allByCountry($country)
    {
        $country = $this->makeCountryFromString($country);

        return $this->all()->filter(function($item) use ($country) {
            return $item['ip_address'] == $country ||
                $this->makeCountryFromString($this->getCountryFromIp($item['ip_address'])) == $country;
        });
    }

    /**
     * Clear all items from all lists.
     *
     * @return int
     */
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

    private function nonDatabaseFind($ip)
    {
        $ips = $this->getNonDatabaseIps();

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

    private function removeFromArrayList($ipAddress)
    {
        $this->removeFromArrayListType('whitelist', $ipAddress);

        $this->removeFromArrayListType('blacklist', $ipAddress);
    }

    private function removeFromArrayListType($type, $ipAddress)
    {
        $data = $this->config->get($type);

        if (($key = array_search($ipAddress, $data)) !== false) {
            unset($data[$key]);
        }

        $this->config->set($type, $data);
    }

    private function removeFromDatabaseList($ipAddress)
    {
        if ($ip = $this->find($ipAddress)) {
            $ip->delete();

            $this->cacheForget($ipAddress);

            return true;
        }

        return false;
    }

    /**
     * Transform a list of ips to a list of models.
     *
     * @param $ipList
     * @return array
     */
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

    private function mergeLists($database_ips, $config_ips)
    {
        return collect($database_ips)
            ->merge(collect($config_ips));
    }

    public function hostToIp($ip)
    {
        if (is_string($ip) && starts_with($ip, $string = 'host:')) {
            return gethostbyname(str_replace($string, '', $ip));
        }

        return $ip;
    }

    /**
     * Check if an IP address is in a secondary (black/white) list.
     *
     * @param $ip_address
     *
     * @return bool
     */
    public function checkSecondaryLists($ip_address)
    {
        foreach ($this->all() as $range) {
            if ($this->hostToIp($range) == $ip_address || $this->ipIsInValidRange($ip_address, $range)) {
                return $range;
            }
        }

        return false;
    }

    /**
     * Check if IP is in a valid range.
     *
     * @param $ip_address
     * @param $range
     *
     * @return bool
     */
    private function ipIsInValidRange($ip_address, $range)
    {
        return $this->config->get('enable_range_search') &&
            IpAddress::ipV4Valid($range->ip_address) &&
            ipv4_in_range($ip_address, $range->ip_address);
    }

    /**
     * Check if a string is a valid country info.
     *
     * @param $country
     *
     * @return bool
     */
    public function validCountry($country)
    {
        $country = strtolower($country);

        if ($this->config->get('enable_country_search')) {
            if (starts_with($country, 'country:') && $this->countries->isValid($country)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add an IP to black or whitelist.
     *
     * @param $whitelist
     * @param $ip
     * @param $force
     *
     * @return bool
     */
    public function addToList($whitelist, $ip, $force)
    {
        $list = $whitelist
            ? 'whitelist'
            : 'blacklist';

        if (!$this->ipIsValid($ip)) {
            $this->messageRepository->addMessage(sprintf('%s is not a valid IP address', $ip));

            return false;
        }

        $listed = $this->whichList($ip);

        if ($listed == $list) {
            $this->messageRepository->addMessage(sprintf('%s is already %s', $ip, $list.'ed'));

            return false;
        } else {
            if (!$listed || $force) {
                if ($listed) {
                    $this->remove($ip);
                }

                $this->addToProperList($whitelist, $ip);

                $this->messageRepository->addMessage(sprintf('%s is now %s', $ip, $list.'ed'));

                return true;
            }
        }

        $this->messageRepository->addMessage(sprintf('%s is currently %sed', $ip, $listed));

        return false;
    }

    /**
     * Tell in which list (black/white) an IP address is.
     *
     * @param $ip_address
     *
     * @return bool|string
     */
    public function whichList($ip_address)
    {
        $ip_address = $ip_address
            ?: $this->getIp();

        if (!$ip_found = $this->find($ip_address)) {
            if (!$ip_found = $this->findByCountry($ip_address)) {
                if (!$ip_found = $this->checkSecondaryLists($ip_address)) {
                    return false;
                }
            }
        }

        if ($ip_found) {
            return $ip_found['whitelisted']
                ? 'whitelist'
                : 'blacklist';
        }

        return false;
    }

    /**
     * Remove IP from all lists.
     *
     * @param $ip
     *
     * @return bool
     */
    public function remove($ip)
    {
        $listed = $this->whichList($ip);

        if ($listed) {
            $this->delete($ip);

            $this->messageRepository->addMessage(sprintf('%s removed from %s', $ip, $listed));

            return true;
        }

        $this->messageRepository->addMessage(sprintf('%s is not listed', $ip));

        return false;
    }

    /**
     * Get the GeoIP instance.
     *
     * @return object
     */
    public function getGeoIp()
    {
        return $this->countries->getGeoIp();
    }
}
