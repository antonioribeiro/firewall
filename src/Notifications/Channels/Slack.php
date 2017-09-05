<?php

namespace PragmaRX\Firewall\Notifications\Channels;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\SlackMessage;

class Slack extends BaseChannel implements Contract
{
    /**
     * Send a message.
     *
     * @param $notifiable
     * @param $item
     *
     * @return \Illuminate\Notifications\Messages\SlackMessage
     */
    public function send($notifiable, $item)
    {
        $message = (new SlackMessage())
            ->error()
            ->from(
                config('firewall.notifications.from.name'),
                config('firewall.notifications.from.icon_emoji')
            )
            ->content($this->getMessage($item))
            ->attachment(function ($attachment) use ($item) {
                $attachment->title(config('firewall.notifications.message.request_count.title'))
                            ->content(
                                sprintf(
                                    config('firewall.notifications.message.request_count.message'),
                                    $item['requestCount'],
                                    $item['firstRequestAt']->diffInSeconds(Carbon::now()),
                                    (string) $item['firstRequestAt']
                                )
                            );
            })
            ->attachment(function ($attachment) use ($item) {
                $attachment->title($title = config('firewall.notifications.message.uri.title'))
                           ->content($item['server']['REQUEST_URI']);
            })
            ->attachment(function ($attachment) use ($item) {
                $attachment->title(config('firewall.notifications.message.user_agent.title'))
                           ->content($item['userAgent']);
            })
            ->attachment(function ($attachment) use ($item) {
                $attachment->title(config('firewall.notifications.message.blacklisted.title'))
                           ->content($item['isBlacklisted'] ? 'YES' : 'NO');
            });

        if ($item['geoIp']) {
            $message->attachment(function ($attachment) use ($item) {
                $attachment->title(config('firewall.notifications.message.geolocation.title'))
                           ->fields($this->makeGeolocation($item));
            });
        }

        return $message;
    }
}
