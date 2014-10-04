<?php

/**
 * Part of the Firewall package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Firewall
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

namespace PragmaRX\Firewall\Filters;

class Whitelist {

    public function filter()
    {
    	$firewall = app()->make('firewall');

        if ( ! $firewall->isWhitelisted()) {
            if ($to = app()->make('firewall.config')->get('redirect_non_whitelisted_to'))
            {
                $action = 'redirected';
                $response = app()->make('redirect')->to($to);
            }
            else
            {
                $action = 'blocked';
                $response = $firewall->blockAccess();
            }

            $message = sprintf('[%s] IP not whitelisted: %s', $action, $firewall->getIp());

            $firewall->log($message);

            return $response;
        }
    }

}
