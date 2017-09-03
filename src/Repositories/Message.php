<?php

namespace PragmaRX\Firewall\Repositories;

class Message
{
    /**
     * Saved messages.
     *
     * @var array
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
     */
    public function addMessage($message)
    {
        collect((array) $message)->each(function($item) {
            collect($item)->flatten()->each(function($flattened) {
                $this->messages->push($flattened);
            });
        });
    }

    /**
     * Get the messages.
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }
}
