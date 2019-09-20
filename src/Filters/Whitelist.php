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
            $responses_whitelist = $this->config()->get('responses.whitelist');

            if (!is_null($this->config()->get('redirect_non_whitelisted_to'))) {
                $responses_whitelist['redirect_to'] = $this->config()->get('redirect_non_whitelisted_to');
            }

            if (!is_null($responses_whitelist['redirect_to'])) {
                $action = 'redirected';
            } else {
                $action = 'blocked';
            }

            $response = (new Responder())->respond(
                $responses_whitelist
            );

            $message = sprintf('[%s] IP not whitelisted: %s', $action, $firewall->getIp());

            $firewall->log($message);

            return $response;
        }
    }
}
