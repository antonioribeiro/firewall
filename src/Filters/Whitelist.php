<?php

namespace PragmaRX\Firewall\Filters;

use PragmaRX\Firewall\Support\Responder;
use PragmaRX\Firewall\Support\ServiceInstances;

class Whitelist
{
    use ServiceInstances;

    public function filter()
    {
        $firewall = app()->make('firewall');

        if (!$firewall->isWhitelisted()) {
            $response = (new Responder())->respond(
                $this->config()->get('responses.whitelist')
            );

            if (!is_null($this->config()->get('responses.whitelist.redirect_to'))) {
                $action = 'redirected';
            } else {
                $action = 'blocked';
            }

            $message = sprintf('[%s] IP not whitelisted: %s', $action, $firewall->getIp());

            $firewall->log($message);

            return $response;
        }
    }
}
