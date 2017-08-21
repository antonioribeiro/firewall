<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Models;

use App\User as AppUser;

class User extends AppUser
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
