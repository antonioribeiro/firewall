<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Http\Response;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class FirewallTestCase extends TestCase
{
    public function testInvalidIp()
    {
        $this->assertFalse(Firewall::blacklist('127.0.0.256'));
    }

    public function testFirewallIsInstantiable()
    {
        $false = Firewall::isBlackListed('impossible');

        $this->assertFalse($false);
    }

    public function testDisableFirewall()
    {
        $this->config('enabled', false);

        Firewall::blacklist($ip = '172.17.0.100');

        $this->assertTrue(Firewall::isBlackListed($ip));
    }

    public function testCanBlacklistIps()
    {
        Firewall::blacklist($ip = '172.17.0.100');

        $this->assertTrue(Firewall::isBlackListed($ip));

        $this->assertFalse(Firewall::isWhitelisted($ip));

        $this->assertFalse(Firewall::isBlackListed('172.17.0.101'));

        $this->assertFalse(Firewall::isWhitelisted('172.17.0.101'));

        $this->assertEquals(Firewall::whichList($ip), 'blacklist');
    }

    public function testCanWhitelistIps()
    {
        Firewall::whitelist($ip = '172.17.0.101');

        $this->assertFalse(Firewall::isBlackListed($ip));

        $this->assertTrue(Firewall::isWhitelisted($ip));

        $this->assertFalse(Firewall::isWhitelisted('172.17.0.102'));

        $this->assertFalse(Firewall::isBlacklisted('172.17.0.102'));

        $this->assertEquals(Firewall::whichList($ip), 'whitelist');
    }

    public function testCanListCidrs()
    {
        Firewall::whitelist('172.17.0.0/24');

        $this->assertTrue(Firewall::isWhitelisted($ip = '172.17.0.1'));

        $this->assertTrue(Firewall::isWhitelisted('172.17.0.100'));

        $this->assertTrue(Firewall::isWhitelisted('172.17.0.255'));

        $this->assertFalse(Firewall::isBlacklisted($ip));
    }

    public function testRefusesWrongIpAddresses()
    {
        $false = Firewall::whitelist('172.17.0.256');

        $this->assertFalse($false);

        $this->assertEquals(Firewall::getMessages()->toArray(), ['172.17.0.256 is not a valid IP address']);

        $this->assertFalse($false);
    }

    public function testForceToAList()
    {
        Firewall::whitelist($ip = '172.17.0.1');

        $this->assertTrue(Firewall::isWhitelisted($ip));

        Firewall::blacklist($ip = '172.17.0.1');

        $this->assertTrue(Firewall::isWhitelisted($ip));

        Firewall::blacklist($ip = '172.17.0.1', true); // force

        $this->assertFalse(Firewall::isWhitelisted($ip));

        $this->assertTrue(Firewall::isBlacklisted($ip));
    }

    public function testFindIp()
    {
        Firewall::whitelist($ip = '172.17.0.1');

        $model = Firewall::find($ip);

        $this->assertInstanceOf(\PragmaRX\Firewall\Vendor\Laravel\Models\Firewall::class, $model);

        $this->assertNull(Firewall::find('impossible'));
    }

    public function testGetAllIps()
    {
        Firewall::whitelist('172.17.0.1');
        Firewall::whitelist('172.17.0.2');
        Firewall::whitelist('172.17.0.3');

        $this->assertCount(3, Firewall::all());

        Firewall::remove('172.17.0.3');

        $this->assertCount(2, Firewall::all());

        Firewall::clear('172.17.0.3');

        $this->assertCount(0, Firewall::all());
    }

    public function testBlockAccess()
    {
        $this->assertInstanceOf(Response::class, Firewall::blockAccess());
    }

    public function testLog()
    {
        $this->assertNull(Firewall::log('whatever'));
    }

    public function testIpValidation()
    {
        $this->assertTrue(Firewall::ipIsValid('172.17.0.100'));

        $this->assertFalse(Firewall::ipIsValid('172.17.0.256'));
    }

    public function testReport()
    {
        Firewall::whitelist('172.17.0.1');
        Firewall::blacklist('172.17.0.2');

        $expected = [
            [
                'ip_address'  => '172.17.0.1',
                'whitelisted' => 1,
            ],
            [
                'ip_address'  => '172.17.0.2',
                'whitelisted' => 0,
            ],
        ];

        $report = Firewall::report()->map(function ($item, $key) {
            return collect($item->toArray())->only(['ip_address', 'whitelisted']);
        })->toArray();

        $this->assertEquals($expected, $report);
    }

    public function testDoNotReinsertExistent()
    {
        Firewall::blacklist('172.17.0.1');

        Firewall::blacklist('172.17.0.1');

        $this->assertTrue(Firewall::isBlacklisted('172.17.0.1'));
    }

    public function testDoNotRemoveNonExistent()
    {
        Firewall::remove('172.17.0.1');

        Firewall::blacklist('172.17.0.1');

        Firewall::remove('172.17.0.1');

        Firewall::remove('172.17.0.1');

        $this->assertFalse(Firewall::isWhitelisted('172.17.0.1'));

        $this->assertFalse(Firewall::isBlacklisted('172.17.0.1'));
    }

    public function testSetip()
    {
        $this->assertEquals('127.0.0.1', Firewall::getIp());

        Firewall::setIp($ip = '127.0.0.2');

        $this->assertEquals($ip, Firewall::getIp());

        Firewall::setIp($ip = '127.0.0.1');

        Firewall::blacklist('127.0.0.1');

        $this->assertTrue(Firewall::isBlacklisted());
    }

    public function testListByHost()
    {
        Firewall::blacklist('host:corinna.antoniocarlosribeiro.com');

        $this->assertTrue(Firewall::isBlacklisted('67.205.143.231'));
    }

    public function testWildcard()
    {
        Firewall::whitelist('172.17.*.*');

        $this->assertTrue(Firewall::isWhitelisted($ip = '172.17.0.100'));

        $this->assertTrue(Firewall::isWhitelisted($ip = '172.17.1.101'));

        $this->assertTrue(Firewall::isWhitelisted($ip = '172.17.2.102'));

        $this->assertTrue(Firewall::isWhitelisted($ip = '172.17.255.255'));
    }
}
