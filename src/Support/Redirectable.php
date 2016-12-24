<?php

namespace PragmaRX\Firewall\Support;

trait Redirectable {
    /**
     * Creates a redirect response for route or address
     *
     * @param $to
     * @return mixed
     */
    public function redirectTo($to)
    {
        if (app()->make('router')->getRoutes()->getByName($to)) {
            $response = app()->make('redirect')->route($to);

            return $response;
        }

        $response = app()->make('redirect')->to($to);

        return $response;
    }
}
