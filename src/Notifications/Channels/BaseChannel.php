<?php

namespace PragmaRX\Firewall\Notifications\Channels;

use Request;

abstract class BaseChannel implements Contract
{
    private function getActionMessage()
    {
        return config('firewall.notifications.message.message');
    }

    /**
     * @param $item
     *
     * @return string
     */
    protected function getMessage($item)
    {
        $domain = Request::server('SERVER_NAME');

        return sprintf($this->getActionMessage(), $domain, $this->makeMessage($item));
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    protected function makeMessage($item)
    {
        $ip = "{$item['ipAddress']} - {$item['host']}";

        if ($item['type'] == 'ip') {
            return "$ip";
        }

        return "{$item['country_code']}-{$item['country_name']} ({$ip})";
    }

    /**
     * Make a geolocation model for the item.
     *
     * @param $item
     *
     * @return array
     */
    public function makeGeolocation($item)
    {
        return collect([
            config('firewall.notifications.message.geolocation.field_latitude')     => $item['geoIp']['latitude'],
            config('firewall.notifications.message.geolocation.field_longitude')    => $item['geoIp']['longitude'],
            config('firewall.notifications.message.geolocation.field_country_code') => $item['geoIp']['country_code'],
            config('firewall.notifications.message.geolocation.field_country_name') => $item['geoIp']['country_name'],
            config('firewall.notifications.message.geolocation.field_city')         => $item['geoIp']['city'],
        ])->filter()->toArray();
    }
}
