<?php

namespace PragmaRX\Firewall\Vendor\Laravel\Artisan;

use Illuminate\Console\Command;

abstract class Base extends Command
{
    public function displayMessages($type, $messages)
    {
        foreach ($messages as $message) {
            $this->$type($message);
        }
    }

    /**
     * Handle the command.
     *
     * @return void
     */
    public function handle()
    {
        $this->fire();
    }

    /**
     * Fire the command.
     *
     * @return void
     */
    public function fireCommand($method, $parameters)
    {
        $instance = app('firewall');

        $type = call_user_func_array([$instance, $method], $parameters)
            ? 'info'
            : 'error';

        $this->displayMessages($type, app('firewall')->getMessages());
    }

    abstract public function fire();
}
