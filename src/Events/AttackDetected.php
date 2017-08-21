<?php

namespace PragmaRX\Firewall\Events;

use Illuminate\Contracts\Queue\ShouldQueue;

class AttackDetected implements ShouldQueue
{
    /**
     * @var
     */
    public $record;

    /**
     * @var
     */
    public $channel;

    /**
     * Create a new event instance.
     */
    public function __construct($record, $channel)
    {
        $this->record = $record;

        $this->channel = $channel;
    }
}
