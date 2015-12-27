<?php

namespace PragmaRX\Firewall\Middleware;

use Closure;
use PragmaRX\Firewall\Filters\Blacklist;

class FirewallBlacklist
{
    protected $blacklist;

    public function __construct(Blacklist $blacklist) {
        $this->blacklist = $blacklist;
    }

    /**
     * Filter Request through blacklist.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $filterResponse = $this->blacklist->filter();

        if ($filterResponse != null) {
            return $filterResponse;
        }

        return $next($request);
    }
}
