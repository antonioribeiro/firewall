<?php

namespace PragmaRX\Firewall\Middleware;

use PragmaRX\Firewall\Filters\Blacklist;

class FirewallBlacklist extends FilterMiddleware
{
    protected $blacklist;

    public function __construct(Blacklist $blacklist)
    {
        $this->blacklist = $blacklist;
    }

    /**
     * Filter Request.
     *
     * @return mixed
     */
    public function filter()
    {
        return $this->blacklist->filter();
    }
}
