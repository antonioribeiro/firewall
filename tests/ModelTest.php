<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Exceptions\ConfigurationOptionNotAvailable;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class ModelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $app['config']->set('firewall.firewall_model', null);

        return parent::getPackageProviders($app);
    }

    public function testModelNotAvailable()
    {
        $this->expectException(ConfigurationOptionNotAvailable::class);

        Firewall::blacklist($ip = '127.0.0.1');
    }
}
