<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Contracts\Container\BindingResolutionException;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class ServiceDisabledTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $app['config']->set('firewall.enabled', false);

        return [
            FirewallServiceProvider::class,
        ];
    }

    public function testFirewallIsDisabled()
    {
        $this->expectException(BindingResolutionException::class);

        Firewall::blacklist($ip = '172.17.0.100');
    }
}
