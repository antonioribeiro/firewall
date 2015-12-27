<?php

namespace PragmaRX\Firewall\Filters;

class Whitelist
{
    public function filter() {
        $firewall = app()->make('firewall');

        if (!$firewall->isWhitelisted()) {
            if ($to = app()->make('firewall.config')->get('redirect_non_whitelisted_to')) {
                $action = 'redirected';

                if (app()->make('router')->getRoutes()->getByName($to)) {
                    $response = app()->make('redirect')->route($to);
                }
                else {
                    $response = app()->make('redirect')->to($to);
                }
            }
            else {
                $action = 'blocked';
                $response = $firewall->blockAccess();
            }

            $message = sprintf('[%s] IP not whitelisted: %s', $action, $firewall->getIp());

            $firewall->log($message);

            return $response;
        }
    }
}
