<?php

namespace PragmaRX\Firewall\Filters;

class Blacklist
{
    public function filter() {
        $firewall = app()->make('firewall');

        if ($firewall->isBlacklisted()) {
            $firewall->log('[blocked] IP blacklisted: ' . $firewall->getIp());

            return $firewall->blockAccess();
        }
    }
}
