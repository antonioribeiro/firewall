<?php

namespace PragmaRX\Firewall\Notifications\Channels;

use Request;

abstract class BaseChannel implements Contract
{
    private function getActionMessage($item)
    {
        return isset($item['message'])
                ? $item['message']
                : config('firewall.notifications.message');
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
     * @return string
     */
    protected function getMessage($item)
    {
        $domain = Request::server('SERVER_NAME');

        return sprintf(
            $this->getActionMessage($item),
            $domain,
            $item['ipAddress']
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
}
