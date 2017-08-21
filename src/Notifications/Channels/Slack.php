<?php

namespace PragmaRX\Firewall\Notifications\Channels;

use Carbon\Carbon;
use Illuminate\Notifications\Messages\SlackMessage;

class Slack extends BaseChannel
{
    /**
     * @param $notifiable
     * @param $item
     *
     * @return $this
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
                $attachment->title('Request count')
                            ->content(
                                sprintf(
                                    'Client made %s requests in the last %s seconds. Timestamp of first request: %s',
                                    $item['requestCount'],
                                    $item['firstRequestAt']->diffInSeconds(Carbon::now()),
                                    (string) $item['firstRequestAt']
                                )
                            );
            })
            ->attachment(function ($attachment) use ($item) {
                $attachment->title('URI')
                           ->content($item['server']['REQUEST_URI']);
            })
            ->attachment(function ($attachment) use ($item) {
                $attachment->title('User agent')
                           ->content($item['userAgent']);
            });

        if ($item['geoIp']) {
            $message->attachment(function ($attachment) use ($item) {
                $attachment->title('User agent')
                           ->content($item['userAgent']);
            });
        }
    }
}
