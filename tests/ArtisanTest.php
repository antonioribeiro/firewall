<?php

namespace PragmaRX\Firewall\Tests;

use Artisan;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class ArtisanTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $this->config('use_database', true);

        return parent::getPackageProviders($app);
    }

    public function testUpdateGeoip()
    {
        $this->assertEquals(0, Artisan::call('firewall:updategeoip'));
    }

    public function testBlacklist()
    {
        Artisan::call('firewall:blacklist', ['ip' => $ip = '127.0.0.1']);

        $this->assertTrue(Firewall::isBlacklisted($ip));
    }

    public function testWhitelist()
    {
        Artisan::call('firewall:whitelist', ['ip' => $ip = '127.0.0.1']);

        $this->assertTrue(Firewall::isWhitelisted($ip));
    }

    public function testRemove()
    {
        Artisan::call('firewall:whitelist', ['ip' => $ip1 = '127.0.0.1']);

        Artisan::call('firewall:blacklist', ['ip' => $ip2 = '127.0.0.2']);

        Artisan::call('firewall:remove', ['ip' => $ip1 = '127.0.0.1']);

        $this->assertFalse(Firewall::isWhitelisted($ip1));

        $this->assertTrue(Firewall::isBlacklisted($ip2));

        Artisan::call('firewall:clear', ['--force' => true]);

        $this->assertFalse(Firewall::isWhitelisted($ip2));
    }

    public function testReport()
    {
        $this->assertEquals(0, Artisan::call('firewall:list'));
    }
}
