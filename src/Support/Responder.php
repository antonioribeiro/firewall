<?php

namespace PragmaRX\Firewall\Support;

use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;

class Responder
{
    /**
     * Properly respond.
     *
     * @var \Illuminate\Support\Collection
     *
     * @return mixed
     */
    public function respond($response, $data = [])
    {
        if ($response['code'] === 200) {
            return;
        }

        if ($response['abort']) {
            abort(
                $response['code'],
                $response['message']
            );
        }

        if ($page = $response['redirect_to']) {
            return Redirect::to($page);
        }

        if ($view = $response['view']) {
            return Response::view($view, $data);
        }

        return Response::make(
            $response['message'],
            $response['code']
        );
    }
}
