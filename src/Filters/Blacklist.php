<?php

namespace PragmaRX\Firewall\Filters;

class Blacklist
{
    public function filter()
    {
        $firewall = app()->make('firewall');

        if ($firewall->isBlacklisted($ipAddress = $firewall->getIp())) {
            $firewall->log('[blocked] IP blacklisted: '.$ipAddress);

            return $firewall->blockAccess();
        }
    }
}
