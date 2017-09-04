<?php

namespace PragmaRX\Firewall\Support;

trait ServiceInstances
{
    public $instances = [];

    public function getInstance($binding)
    {
        if (is_null($instance = $this->getFromInstanceCache($binding))) {
            $instance = app('firewall.'.$binding);
        }

        $this->setInstance($binding, $instance);

        return $instance;
    }

    public function getFromInstanceCache($binding)
    {
        if (isset($this->instances[$binding])) {
            return $this->instances[$binding];
        }

        return null;
    }

    public function setInstance($binding, $instance)
    {
        $this->instances[$binding] = $instance;
    }

    public function countries()
    {
        return $this->getInstance('countries');
    }

    public function ipList()
    {
        return $this->getInstance('iplist');
    }

    public function messages()
    {
        return $this->getInstance('messages');
    }

    public function cache()
    {
        return $this->getInstance('cache');
    }

    public function config()
    {
        return $this->getInstance('config');
    }

    public function fileSystem()
    {
        return $this->getInstance('filesystem');
    }

    public function geoIp()
    {
        return $this->getInstance('geoip');
    }

    public function ipAddress()
    {
        return $this->getInstance('ipaddress');
    }

    public function dataRepository()
    {
        return $this->getInstance('datarepository');
    }
}
