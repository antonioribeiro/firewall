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
     * @return mixed
     */
    protected function getActionTitle()
    {
        return config('firewall.notifications.title');
    }

    /**
     * @param $item
     *
     * @return string
     */
    protected function getMessage($item)
    {
        $domain = Request::server('SERVER_NAME');

        return sprintf(
            $this->getActionMessage($item),
            $domain,
            $this->makeMessage($item)
        );
    }

    /**
     * @return string
     */
    protected function getActionLink()
    {
        if ($route = config('firewall.notification.route')) {
            return route($route);
        }
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
}
