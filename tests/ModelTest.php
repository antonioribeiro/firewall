<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use PragmaRX\Firewall\Exceptions\ConfigurationOptionNotAvailable;

class ModelTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $app['config']->set('firewall.firewall_model', null);

        return parent::getPackageProviders($app);
    }

    public function test_model_not_available()
    {
        $this->expectException(ConfigurationOptionNotAvailable::class);

        Firewall::blacklist($ip = '127.0.0.1');
    }
}
