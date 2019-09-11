<?php

namespace PragmaRX\Firewall\Repositories;

use PragmaRX\Firewall\Support\ServiceInstances;
use PragmaRX\Firewall\Vendor\Laravel\Models\Firewall as FirewallModel;

class IpList
{
    use ServiceInstances;

    const IP_ADDRESS_LIST_CACHE_NAME = 'firewall.ip_address_list';

    /**
     * @var \PragmaRX\Firewall\Vendor\Laravel\Models\Firewall
     */
    private $model;

    /**
     * Create instance of DataRepository.
     *
     * @param \PragmaRX\Firewall\Vendor\Laravel\Models\Firewall $model
     */
    public function __construct(FirewallModel $model)
    {
        $this->model = $model;
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
            }, $this->formatIpArray($this->config()->get('whitelist'))),

            array_map(function ($ip) {
                $ip['whitelisted'] = false;

                return $ip;
            }, $this->formatIpArray($this->config()->get('blacklist')))
        );
    }

    /**
     * Remove ip from all array lists.
     *
     * @param $ip
     *
     * @return bool
     */
    private function removeFromArrayList($ip)
    {
        return $this->removeFromArrayListType('whitelist', $ip) ||
            $this->removeFromArrayListType('blacklist', $ip);
    }

    /**
     * Remove the ip address from an array list.
     *
     * @param $type
     * @param $ip
     *
     * @return bool
     */
    private function removeFromArrayListType($type, $ip)
    {
        if (($key = array_search($ip, $data = $this->config()->get($type))) !== false) {
            unset($data[$key]);

            $this->cache()->forget($ip);

            $this->config()->set($type, $data);

            $this->messages()->addMessage(sprintf('%s removed from %s', $ip, $type));

            return true;
        }

        return false;
    }

    /**
     * Remove ip from database.
     *
     * @param \Illuminate\Database\Eloquent\Model $ip
     *
     * @return bool
     */
    private function removeFromDatabaseList($ip)
    {
        if ($ip = $this->find($ip)) {
            $ip->delete();

            $this->cache()->forget($ip->ip_address);

            $this->messages()->addMessage(sprintf('%s removed from %s', $ip, $ip->whitelisted ? 'whitelist' : 'blacklist'));
        }
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
        if ($this->fileSystem()->exists($file)) {
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
        $list = (array) $list ?: [];

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

        $item = $this->ipAddress()->hostToIp($item);

        if ($this->ipAddress()->ipV4Valid($item)) {
            return [$item];
        }

        return $this->readFile($item);
    }

    /**
     * Search for an ip in a list of ips.
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
        if ($this->config()->get('use_database')) {
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
     * Check if an IP address is in a secondary (black/white) list.
     *
     * @param $ip_address
     *
     * @return bool|array
     */
    public function checkSecondaryLists($ip_address)
    {
        foreach ($this->all() as $range) {
            if ($this->ipAddress()->hostToIp($range->ip_address) == $ip_address || $this->ipAddress()->validRange($ip_address, $range)) {
                return $range;
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

        if (!$this->ipAddress()->isValid($ip)) {
            return false;
        }

        $listed = $this->whichList($ip);

        if ($listed == $list) {
            $this->messages()->addMessage(sprintf('%s is already %s', $ip, $list.'ed'));

            return false;
        } else {
            if (empty($listed) || $force) {
                if (!empty($listed)) {
                    $this->remove($ip);
                }

                $this->addToProperList($whitelist, $ip);

                $this->messages()->addMessage(sprintf('%s is now %s', $ip, $list.'ed'));

                return true;
            }
        }

        $this->messages()->addMessage(sprintf('%s is currently %sed', $ip, $listed));

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

            return true;
        }

        $this->messages()->addMessage(sprintf('%s is not listed', $ip));

        return false;
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
        $data = $this->config()->get($list = $whitelist ? 'whitelist' : 'blacklist');

        $data[] = $ip;

        $this->config()->set($list, $data);

        return $data;
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
        if ($this->cache()->has($ip)) {
            return $this->cache()->get($ip);
        }

        if ($model = $this->findIp($ip)) {
            $this->cache()->remember($model);
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
        if ($this->config()->get('enable_country_search') && !is_null($country = $this->countries()->makeCountryFromString($country))) {
            return $this->find($country);
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
        $this->config()->get('use_database') ?
            $this->addToDatabaseList($whitelist, $ip) :
            $this->addToArrayList($whitelist, $ip);
    }

    /**
     * Delete ip address.
     *
     * @param $ip
     *
     * @return bool|void
     */
    public function delete($ip)
    {
        $this->config()->get('use_database') ?
            $this->removeFromDatabaseList($ip) :
            $this->removeFromArrayList($ip);
    }

    /**
     * Get all IP addresses.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        $cacheTime = $this->config()->get('ip_list_cache_expire_time');

        if ($cacheTime > 0 && $list = $this->cache()->get(static::IP_ADDRESS_LIST_CACHE_NAME)) {
            return $list;
        }

        $list = $this->mergeLists(
            $this->getAllFromDatabase(),
            $this->toModels($this->getNonDatabaseIps())
        );

        if ($cacheTime > 0) {
            $this->cache()->put(static::IP_ADDRESS_LIST_CACHE_NAME, $list, $cacheTime);
        }

        return $list;
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

        if ($this->config()->get('use_database')) {
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

        $this->cache()->remember($model);

        return $model;
    }
}
