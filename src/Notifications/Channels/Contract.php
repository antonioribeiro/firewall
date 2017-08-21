<?php

namespace PragmaRX\Firewall\Notifications\Channels;

interface Contract
{
    /**
     * @param $notifiable
     * @param $item
     *
     * @return mixed
     */
    public function send($notifiable, $item);
}
