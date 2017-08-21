<?php

namespace PragmaRX\Firewall\Middleware;

use Closure;
use PragmaRX\Firewall\Filters\Whitelist;

class FirewallWhitelist extends Middleware
{
    protected $whitelist;

    public function __construct(Whitelist $whitelist)
    {
        $this->whitelist = $whitelist;
    }

    /**
     * Filter Request through whitelist.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->enabled()) {
            $filterResponse = $this->whitelist->filter();

            if ($filterResponse != null) {
                return $filterResponse;
            }
        }

        return $next($request);
    }
}
