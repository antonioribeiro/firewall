<?php

namespace PragmaRX\Firewall\Notifications\Channels;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;

class Mail extends BaseChannel implements Contract
{
    /**
     * Send a message.
     *
     * @param $notifiable
     * @param $item
     *
     * @return null|\Illuminate\Notifications\Messages\SlackMessage
     */
    public function send($notifiable, $item)
    {
        $message = (new MailMessage())
            ->from(
                config('firewall.notifications.from.address'),
                config('firewall.notifications.from.name').' '.
                config('firewall.notifications.from.icon_emoji')
            )
            ->subject(config('firewall.notifications.message.request_count.title'))
            ->line(sprintf(
                config('firewall.notifications.message.request_count.message'),
                $item['requestCount'],
                $item['firstRequestAt']->diffInSeconds(Carbon::now()),
                (string) $item['firstRequestAt']
            ))
            ->line(config('firewall.notifications.message.uri.title').': '.$item['server']['REQUEST_URI'])
            ->line(config('firewall.notifications.message.user_agent.title').': '.$item['userAgent'])
            ->line(config('firewall.notifications.message.blacklisted.title').': '.$item['isBlacklisted'] ? 'YES' : 'NO');

        $geo = $this->makeGeolocation($item);

        if ($item['geoIp']) {
            $message->line(config('firewall.notifications.message.geolocation.title')." - Latitude : {$geo['Latitude']}");
            $message->line(config('firewall.notifications.message.geolocation.title')." - Longitude : {$geo['Longitude']}");
            $message->line(config('firewall.notifications.message.geolocation.title')." - Country code : {$geo['Country code']}");
            $message->line(config('firewall.notifications.message.geolocation.title')." - Country name : {$geo['Country name']}");
        }

        return $message;
    }
}
