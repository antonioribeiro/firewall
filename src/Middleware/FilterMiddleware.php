<?php

namespace PragmaRX\Firewall\Middleware;

use Closure;
use Illuminate\Http\Request;

abstract class FilterMiddleware extends Middleware
{
    public function enabled()
    {
        return config('firewall.enabled');
    }

    /**
     * Filter Request through whitelist.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->enabled()) {
            $filterResponse = $this->filter();

            if ($filterResponse !== null) {
                return $filterResponse;
            }
        }

        return $next($request);
    }

    /**
     * Filter.
     *
     * @return mixed
     */
    abstract public function filter();
}
