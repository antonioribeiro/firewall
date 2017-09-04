<?php

namespace PragmaRX\Firewall\Repositories;

use PragmaRX\Firewall\Support\ServiceInstances;

class DataRepository
{
    use ServiceInstances;

    /**
     * @var \PragmaRX\Firewall\Firewall
     */
    public $firewall;

    /**
     * Check if a string is a valid country info.
     *
     * @param $country
     *
     * @return bool
     */
    public function validCountry($country)
    {
        return $this->countries()->validCountry($country);
    }

    /**
     * Get country code from an IP address.
     *
     * @param $ip_address
     *
     * @return string|null
     */
    public function getCountryFromIp($ip_address)
    {
        return $this->countries()->getCountryFromIp($ip_address);
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

        return $this->ipList()->all()->filter(function ($item) use ($country) {
            return $item['ip_address'] == $country ||
                $this->makeCountryFromString($this->getCountryFromIp($item['ip_address'])) == $country;
        });
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
        return $this->countries()->makeCountryFromString($country);
    }

    /**
     * Clear all items from all lists.
     *
     * @return int
     */
    public function clear()
    {
        $deleted = 0;

        foreach ($this->ipList()->all() as $ip) {
            if ($this->remove($ip['ip_address'])) {
                $deleted++;
            }
        }

        return $deleted;
    }

    /**
     * Get the GeoIP instance.
     *
     * @return \PragmaRX\Support\GeoIp\GeoIp
     */
    public function getGeoIp()
    {
        return $this->countries()->getGeoIp();
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
        return $this->ipList()->addToList($whitelist, $ip, $force);
    }

    /**
     * Get all IP addresses.
     *
     * @return \Illuminate\Support\Collection
     */
    public function all()
    {
        return $this->ipList()->all();
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
        return $this->ipList()->whichList($ip_address);
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
        return $this->ipAddress()->isValid($ip);
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
        return $this->ipList()->find($ip);
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
        return $this->ipList()->remove($ip);
    }
}
