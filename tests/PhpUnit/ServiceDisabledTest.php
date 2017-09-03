<?php

namespace PragmaRX\Firewall\Tests\PhpUnit;

use ReflectionException;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use PragmaRX\Firewall\Tests\PhpUnit\TestCase;
use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class ServiceDisabledTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $app['config']->set('firewall.enabled', false);

        return [
            FirewallServiceProvider::class
        ];
    }

    public function test_firewall_is_disabled()
    {
        $this->expectException(ReflectionException::class);

        Firewall::blacklist($ip = '172.17.0.100');
    }
}
