<?php

namespace PragmaRX\Firewall\Middleware;

use Closure;

class BlockAttacks extends Middleware
{
    /**
     * Block attacks.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->enabled() && app('firewall')->isBeingAttacked()) {
            return app('firewall')->responseToAttack() ?: $next($request);
        }

        return $next($request);
    }
}
