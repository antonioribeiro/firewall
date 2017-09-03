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

    public function handle()
    {
        $this->fire();
    }

    public function fireCommand($method, $parameters)
    {
        $type = call_user_func_array ( [$this->laravel->firewall, $method], $parameters)
            ? 'info'
            : 'error';

        $this->displayMessages($type, $this->laravel->firewall->getMessages());
    }
}
