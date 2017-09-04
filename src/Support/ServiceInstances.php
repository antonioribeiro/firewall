<?php

namespace PragmaRX\Firewall\Support;

trait ServiceInstances
{
    public $instances = [];

    /**
     * Get an instance.
     *
     * @param $binding
     * @param $instance
     */
    public function getInstance($binding)
    {
        if (is_null($instance = $this->getFromInstanceCache($binding))) {
            $instance = app('firewall.'.$binding);
        }

        $this->setInstance($binding, $instance);

        return $instance;
    }

    /**
     * Get an instance from the instance memory cache.
     *
     * @param $binding
     * @param $instance
     */
    public function getFromInstanceCache($binding)
    {
        if (isset($this->instances[$binding])) {
            return $this->instances[$binding];
        }
    }

    /**
     * Set the instance.
     *
     * @param $binding
     * @param $instance
     */
    public function setInstance($binding, $instance)
    {
        $this->instances[$binding] = $instance;
    }

    /**
     * Get the Countries instance.
     *
     * @return \PragmaRX\Firewall\Repositories\Countries
     */
    public function countries()
    {
        return $this->getInstance('countries');
    }

    /**
     * Get the IpList instance.
     *
     * @return \PragmaRX\Firewall\Repositories\IpList
     */
    public function ipList()
    {
        return $this->getInstance('iplist');
    }

    /**
     * Get the MessageRepository instance.
     *
     * @return \PragmaRX\Firewall\Repositories\Message
     */
    public function messages()
    {
        return $this->getInstance('messages');
    }

    /**
     * Get the Cache instance.
     *
     * @return \PragmaRX\Firewall\Repositories\Cache\Cache
     */
    public function cache()
    {
        return $this->getInstance('cache');
    }

    /**
     * Get the Config instance.
     *
     * @return \PragmaRX\Support\Config
     */
    public function config()
    {
        return $this->getInstance('config');
    }

    /**
     * Get the FileSystem instance.
     *
     * @return \PragmaRX\Support\Filesystem
     */
    public function fileSystem()
    {
        return $this->getInstance('filesystem');
    }

    /**
     * Get the GeoIp instance.
     *
     * @return \PragmaRX\Support\GeoIp\GeoIp
     */
    public function geoIp()
    {
        return $this->getInstance('geoip');
    }

    /**
     * Get the IpAddress instance.
     *
     * @return \PragmaRX\Firewall\Support\IpAddress
     */
    public function ipAddress()
    {
        return $this->getInstance('ipaddress');
    }

    /**
     * Get the DataRepository instance.
     *
     * @return \PragmaRX\Firewall\Repositories\DataRepository
     */
    public function dataRepository()
    {
        return $this->getInstance('datarepository');
    }
}
