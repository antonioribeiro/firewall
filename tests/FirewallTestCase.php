<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Http\Response;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class FirewallTestCase extends TestCase
{
    public function test_invalid_ip()
    {
        $this->assertFalse(Firewall::blacklist('127.0.0.256'));
    }

    public function test_firewall_is_instantiable()
    {
        $false = Firewall::isBlackListed('impossible');

        $this->assertFalse($false);
    }

    public function test_disable_firewall()
    {
        $this->config('enabled', false);

        Firewall::blacklist($ip = '172.17.0.100');

        $this->assertTrue(Firewall::isBlackListed($ip));
    }

    public function test_can_blacklist_ips()
    {
        Firewall::blacklist($ip = '172.17.0.100');

        $this->assertTrue(Firewall::isBlackListed($ip));

        $this->assertFalse(Firewall::isWhitelisted($ip));

        $this->assertFalse(Firewall::isBlackListed('172.17.0.101'));

        $this->assertFalse(Firewall::isWhitelisted('172.17.0.101'));

        $this->assertEquals(Firewall::whichList($ip), 'blacklist');
    }

    public function test_can_whitelist_ips()
    {
        Firewall::whitelist($ip = '172.17.0.101');

        $this->assertFalse(Firewall::isBlackListed($ip));

        $this->assertTrue(Firewall::isWhitelisted($ip));

        $this->assertFalse(Firewall::isWhitelisted('172.17.0.102'));

        $this->assertFalse(Firewall::isBlacklisted('172.17.0.102'));

        $this->assertEquals(Firewall::whichList($ip), 'whitelist');
    }

    public function test_can_list_cidrs()
    {
        Firewall::whitelist('172.17.0.0/24');

        $this->assertTrue(Firewall::isWhitelisted($ip = '172.17.0.1'));

        $this->assertTrue(Firewall::isWhitelisted('172.17.0.100'));

        $this->assertTrue(Firewall::isWhitelisted('172.17.0.255'));

        $this->assertFalse(Firewall::isBlacklisted($ip));
    }

    public function test_refuses_wrong_ip_addresses()
    {
        $false = Firewall::whitelist('172.17.0.256');

        $this->assertFalse($false);

        $this->assertEquals(Firewall::getMessages()->toArray(), ['172.17.0.256 is not a valid IP address']);

        $this->assertFalse($false);
    }

    public function test_force_to_a_list()
    {
        Firewall::whitelist($ip = '172.17.0.1');

        $this->assertTrue(Firewall::isWhitelisted($ip));

        Firewall::blacklist($ip = '172.17.0.1');

        $this->assertTrue(Firewall::isWhitelisted($ip));

        Firewall::blacklist($ip = '172.17.0.1', true); // force

        $this->assertFalse(Firewall::isWhitelisted($ip));

        $this->assertTrue(Firewall::isBlacklisted($ip));
    }

    public function test_find_ip()
    {
        Firewall::whitelist($ip = '172.17.0.1');

        $model = Firewall::find($ip);

        $this->assertInstanceOf(\PragmaRX\Firewall\Vendor\Laravel\Models\Firewall::class, $model);

        $this->assertNull(Firewall::find('impossible'));
    }

    public function test_get_all_ips()
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

    public function test_block_access()
    {
        $this->assertInstanceOf(Response::class, Firewall::blockAccess());
    }

    public function test_log()
    {
        Firewall::log('whatever');
    }

    public function test_ip_validation()
    {
        $this->assertTrue(Firewall::ipIsValid('172.17.0.100'));

        $this->assertFalse(Firewall::ipIsValid('172.17.0.256'));
    }

    public function test_report()
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

    public function test_attack()
    {
        $this->config('notifications.enabled', false);

        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);

        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));
        $this->assertFalse(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertNull(Firewall::responseToAttack());

        $this->assertTrue(Firewall::isBeingAttacked('172.17.0.1'));

        $this->assertInstanceOf(Response::class, Firewall::responseToAttack());
    }

    public function test_do_not_reinsert_existent()
    {
        Firewall::blacklist('172.17.0.1');

        Firewall::blacklist('172.17.0.1');

        $this->assertTrue(Firewall::isBlacklisted('172.17.0.1'));
    }

    public function test_do_not_remove_non_existent()
    {
        Firewall::remove('172.17.0.1');

        Firewall::blacklist('172.17.0.1');

        Firewall::remove('172.17.0.1');

        Firewall::remove('172.17.0.1');

        $this->assertFalse(Firewall::isWhitelisted('172.17.0.1'));

        $this->assertFalse(Firewall::isBlacklisted('172.17.0.1'));
    }

    public function test_setip()
    {
        $this->assertEquals('127.0.0.1', Firewall::getIp());

        Firewall::setIp($ip = '127.0.0.2');

        $this->assertEquals($ip, Firewall::getIp());

        Firewall::setIp($ip = '127.0.0.1');

        Firewall::blacklist('127.0.0.1');

        $this->assertTrue(Firewall::isBlacklisted());
    }

    public function test_list_by_host()
    {
        Firewall::blacklist('host:corinna.antoniocarlosribeiro.com');

        $this->assertTrue(Firewall::isBlacklisted('67.205.143.231'));
    }
}
