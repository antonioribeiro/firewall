<?php

namespace PragmaRX\Firewall\Middleware;

use PragmaRX\Firewall\Filters\Whitelist;

class FirewallWhitelist extends FilterMiddleware
{
    protected $whitelist;

    public function __construct(Whitelist $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Filter Request.
     *
     * @return mixed
     */
    public function filter()
    {
        return $this->whitelist->filter();
    }
}
