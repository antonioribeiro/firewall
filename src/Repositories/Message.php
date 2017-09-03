<?php

namespace PragmaRX\Firewall\Repositories;

class Message
{
    /**
     * Saved messages.
     *
     * @var \Illuminate\Support\Collection
     */
    private $messages;

    public function __construct()
    {
        $this->messages = collect();
    }

    /**
     * Add a message to the messages list.
     *
     * @param $message
     *
     * @return void
     */
    public function addMessage($message)
    {
        collect((array) $message)->each(function ($item) {
            collect($item)->flatten()->each(function ($flattened) {
                $this->messages->push($flattened);
            });
        });
    }

    /**
     * Get the messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
