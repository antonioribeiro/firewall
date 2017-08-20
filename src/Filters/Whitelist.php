<?php

namespace PragmaRX\Firewall\Filters;

use PragmaRX\Firewall\Support\Redirectable;

class Whitelist
{
    use Redirectable;

    public function filter()
    {
        $firewall = app()->make('firewall');

        if (!$firewall->isWhitelisted()) {
            if ($to = app()->make('firewall.config')->get('redirect_non_whitelisted_to')) {
                $action = 'redirected';

                $response = $this->redirectTo($to);
            } else {
                $action = 'blocked';
                $response = $firewall->blockAccess();
            }

            $message = sprintf('[%s] IP not whitelisted: %s', $action, $firewall->getIp());

            $firewall->log($message);

            return $response;
        }
    }
}
