<?php

namespace PragmaRX\Firewall\Middleware;

use Closure;

class BlockAttacks
{
    /**
     * Block attacks.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        if ($response = app('firewall')->isBeingAttacked()) {
            return $response;
        }

        return $next($request);
    }
}
