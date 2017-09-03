<?php

namespace PragmaRX\Firewall\Middleware;

abstract class Middleware
{
    public function enabled()
    {
        return config('firewall.enabled');
    }
}
