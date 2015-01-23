<?php namespace PragmaRX\Firewall\Vendor\Laravel\Models;

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
 * @author     Maurizio Brioschi <maurizio.brioschi@ridesoft.org>
 * @license    BSD License (3-clause)
 */

use Config;

abstract class FirewallFactoryAbstract {
    
    protected $driver;

    public function __construct() {
        $this->setDriver();
    }
    
    protected function setDriver()  {
        if (!isset($this->obj)) {
            if (Config::get('database.default') == 'mongodb') {
                $this->driver = new FirewallMongoDB();
            } else {
                $this->driver = new Firewall();
            }
        }
    }
    
    public function getFirewall(){
        return $this->driver;
    }
}
