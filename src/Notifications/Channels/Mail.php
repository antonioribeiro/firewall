<?php

namespace PragmaRX\Firewall\Notifications\Channels;

use Illuminate\Notifications\Messages\MailMessage;

class Mail extends BaseChannel
{
    /**
     * @param $notifiable
     * @param $item
     * @return $this
     */
    public function send($notifiable, $item)
    {
        return (new MailMessage)
            ->line($this->getMessage($item))
            ->from(
                config('firewall.notifications.from.address'),
                config('firewall.notifications.from.name')
            )
            ->action($this->getActionTitle(), $this->getActionLink());
    }
}
