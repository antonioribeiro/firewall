<?php

namespace PragmaRX\Firewall\Tests;

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
        if (class_exists('Illuminate\Contracts\Container\BindingResolutionException')) {
            $this->expectException(\Illuminate\Contracts\Container\BindingResolutionException::class);
        } else {
            $this->expectException(\ReflectionException::class);
        }

        Firewall::blacklist($ip = '172.17.0.100');
    }
}
