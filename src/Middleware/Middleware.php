<?php

namespace PragmaRX\Firewall\Middleware;

class Middleware
{
    public function enabled()
    {
        return config('firewall.enabled');
    }
}
