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
 * @version    1.0.0
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

return array(

    'create_firewall_alias' => true,

    'firewall_alias' => 'Firewall',

    /*
    |--------------------------------------------------------------------------
    | Do you wish to block access automatically?
    |--------------------------------------------------------------------------
    |
    */

    'block_response_code' => 403,

    'block_response_message' => null,

    'redirect_non_whitelisted_to' => null,

    /*
    |--------------------------------------------------------------------------
    | Which PHP Framework is your application using?
    |--------------------------------------------------------------------------
    |
    |   Supported: "laravel", "none"
    |   
    */

    'framework' => 'laravel',

    /*
    |--------------------------------------------------------------------------
    | Default Database Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the database driver that will be utilized.
    |
    |
    */

    'driver' => 'eloquent',

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | When using the "eloquent" driver, we need to know which Eloquent models 
    | should be used.
    |
    */

    'firewall_model' => 'PragmaRX\Firewall\Vendor\Laravel\Models\Firewall',

);
