<?php

namespace PragmaRX\Firewall\Middleware;

use Closure;

class Middleware
{
    public function enabled()
    {
        return config('firewall.enabled');
    }
}
