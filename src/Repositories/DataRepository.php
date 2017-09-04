<?php

namespace PragmaRX\Firewall\Repositories;

use Exception;
use PragmaRX\Support\Config;
use PragmaRX\Support\IpAddress;
use PragmaRX\Support\Filesystem;
use PragmaRX\Firewall\Repositories\Cache\Cache;
use PragmaRX\Firewall\Vendor\Laravel\Models\Firewall as FirewallModel;

class DataRepository implements DataRepositoryInterface
{
    const IP_ADDRESS_LIST_CACHE_NAME = 'firewall.ip_address_list';

    /**
     * @var \PragmaRX\Firewall\Firewall
     */
    public $firewall;

    /**
     * @var \PragmaRX\Firewall\Repositories\Countries
     */
    public $countries;

    /**
     * @var FirewallModel
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
     * @param FirewallModel $model
     * @param Config        $config
     * @param CacheManager  $cache
     * @param Filesystem    $fileSystem
     * @param Countries     $countries
     * @param Message       $messageRepository
     */
    public function __construct(
        FirewallModel $model,
        Config $config,
        Cache $cache,
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
     *
     * @return array|mixed
     */
    private function addToArrayList($whitelist, $ip)
    {
        $data = $this->config->get($list = $whitelist ? 'whitelist' : 'blacklist');

        $data[] = $ip;

        $this->config->set($list, $data);

        return $data;
    }

    /**
     * Add ip or range to database.
     *
     * @param $whitelist
     * @param $ip
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function addToDatabaseList($whitelist, $ip)
    {
        $this->model->unguard();

        $model = $this->model->create([
            'ip_address'  => $ip,
            'whitelisted' => $whitelist,
        ]);

        $this->cache->remember($model);

        return $model;
    }

    /**
     * Find an IP address in the data source.
     *
     * @param string $ip
     *
     * @return mixed
     */
    public function find($ip)
    {
        if ($this->cache->has($ip)) {
            return $this->cache->get($ip);
        }

        if ($model = $this->findIp($ip)) {
            $this->cache->remember($model);
        }

        return $model;
    }

    /**
     * Find an IP address by country.
     *
     * @param $country
     *
     * @return mixed
     */
    public function findByCountry($country)
    {
        if ($this->config->get('enable_country_search') && !is_null($country = $this->makeCountryFromString($country))) {
            return $this->find($country);
        }
    }

    /**
     * Make a country info from a string.
     *
     * @param $country
     *
     * @return string
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
     * @return string
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
     * @return void
     */
    public function addToProperList($whitelist, $ip)
    {
        $this->config->get('use_database') ?
            $this->addToDatabaseList($whitelist, $ip) :
            $this->addToArrayList($whitelist, $ip);
    }

    /**
     * Delete ip address.
     *
     * @param $ipAddress
     *
     * @return bool|void
     */
    public function delete($ipAddress)
    {
        $this->config->get('use_database') ?
            $this->removeFromDatabaseList($ipAddress) :
            $this->removeFromArrayList($ipAddress);
    }

    /**
     * Get all IP addresses.
     *
     * @return \Illuminate\Support\Collection
     */
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
     *
     * @return \Illuminate\Support\Collection
     */
    public function allByCountry($country)
    {
        $country = $this->makeCountryFromString($country);

        return $this->all()->filter(function ($item) use ($country) {
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
        $deleted = 0;

        foreach ($this->all() as $ip) {
            if ($this->delete($ip['ip_address'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Find ip address in all lists.
     *
     * @param $ip
     *
     * @return \Illuminate\Database\Eloquent\Model|null|static
     */
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
     * Find ip in non database lists.
     *
     * @param $ip
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function nonDatabaseFind($ip)
    {
        $ips = $this->getNonDatabaseIps();

        if ($ip = $this->ipArraySearch($ip, $ips)) {
            return $this->makeModel($ip);
        }
    }

    /**
     * Get a list of non database ip addresses.
     *
     * @return array
     */
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

    /**
     * Remove ip from all array lists.
     *
     * @param $ipAddress
     *
     * @return bool
     */
    private function removeFromArrayList($ipAddress)
    {
        return $this->removeFromArrayListType('whitelist', $ipAddress) ||
            $this->removeFromArrayListType('blacklist', $ipAddress);
    }

    /**
     * Remove the ip address from an array list.
     *
     * @param $type
     * @param $ipAddress
     *
     * @return bool
     */
    private function removeFromArrayListType($type, $ipAddress)
    {
        if (($key = array_search($ipAddress, $data = $this->config->get($type))) !== false) {
            unset($data[$key]);

            $this->config->set($type, $data);

            return true;
        }

        return false;
    }

    /**
     * Remove ip from database.
     *
     * @param $ipAddress
     *
     * @return bool
     */
    private function removeFromDatabaseList($ipAddress)
    {
        if ($ip = $this->find($ipAddress)) {
            $ip->delete();

            $this->cache->forget($ipAddress);

            return true;
        }

        return false;
    }

    /**
     * Transform a list of ips to a list of models.
     *
     * @param $ipList
     *
     * @return \Illuminate\Support\Collection
     */
    private function toModels($ipList)
    {
        $ips = [];

        foreach ($ipList as $ip) {
            $ips[] = $this->makeModel($ip);
        }

        return collect($ips);
    }

    /**
     * Make a model instance.
     *
     * @param $ip
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function makeModel($ip)
    {
        return $this->model->newInstance($ip);
    }

    /**
     * Read a file contents.
     *
     * @param $file
     *
     * @return array
     */
    private function readFile($file)
    {
        if ($this->fileSystem->exists($file)) {
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            return $this->makeArrayOfIps($lines);
        }

        return [];
    }

    /**
     * Format all ips in an array.
     *
     * @param $list
     *
     * @return array
     */
    private function formatIpArray($list)
    {
        return array_map(function ($ip) {
            return ['ip_address' => $ip];
        }, $this->makeArrayOfIps($list));
    }

    /**
     * Make a list of arrays from all sort of things.
     *
     * @param $list
     *
     * @return array
     */
    private function makeArrayOfIps($list)
    {
        $list = $list ?: [];

        $ips = [];

        foreach ($list as $item) {
            $ips = array_merge($ips, $this->getIpsFromAnything($item));
        }

        return $ips;
    }

    /**
     * Get a list of ips from anything.
     *
     * @param $item
     *
     * @return array
     */
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

    /**
     * Search for an ip in alist of ips.
     *
     * @param $ip
     * @param $ips
     *
     * @return null|\Illuminate\Database\Eloquent\Model
     */
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
    }

    /**
     * Get all IPs from database.
     *
     * @return \Illuminate\Support\Collection
     */
    private function getAllFromDatabase()
    {
        if ($this->config->get('use_database')) {
            return $this->model->all();
        } else {
            return collect([]);
        }
    }

    /**
     * Merge IP lists.
     *
     * @param $database_ips
     * @param $config_ips
     *
     * @return \Illuminate\Support\Collection
     */
    private function mergeLists($database_ips, $config_ips)
    {
        return collect($database_ips)
            ->merge(collect($config_ips));
    }

    /**
     * Get the ip address of a host.
     *
     * @param $ip
     *
     * @return string
     */
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
     * @return bool|array
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
            IpAddress::ipv4InRange($ip_address, $range->ip_address);
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
     * @param bool $force
     *
     * @return bool
     */
    public function addToList($whitelist, $ip, $force = false)
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
            if (empty($listed) || $force) {
                if (!empty($listed)) {
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
     * @return null|string
     */
    public function whichList($ip_address)
    {
        if (!$ip_found = $this->find($ip_address)) {
            if (!$ip_found = $this->findByCountry($ip_address)) {
                if (!$ip_found = $this->checkSecondaryLists($ip_address)) {
                    return;
                }
            }
        }

        return !is_null($ip_found)
            ? ($ip_found['whitelisted'] ? 'whitelist' : 'blacklist')
            : null;
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

        if (!empty($listed)) {
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
