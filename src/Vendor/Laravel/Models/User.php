<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Models;

class User
{
    /**
     * Route notifications for the Email channel.
     *
     * @return string
     */
    public function routeNotificationForEmail()
    {
        return $this->email;
    }

    /**
     * Route notifications for the Slack channel.
     *
     * @return string
     */
    public function routeNotificationForSlack()
    {
        return config('services.slack.webhook_url');
    }
}
