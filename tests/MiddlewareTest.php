<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PragmaRX\Firewall\Filters\Blacklist;
use PragmaRX\Firewall\Filters\Whitelist;
use PragmaRX\Firewall\Middleware\BlockAttacks;
use PragmaRX\Firewall\Middleware\FirewallBlacklist;
use PragmaRX\Firewall\Middleware\FirewallWhitelist;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;

class MiddlewareTest extends TestCase
{
    private function getNextClosure()
    {
        return function () {
            return 'next';
        };
    }

    public function setUp(): void
    {
        parent::setUp();

        $this->request = new Request();

        $this->blockAttacks = (new BlockAttacks());

        $this->blacklist = (new FirewallBlacklist(new Blacklist()));

        $this->whitelist = (new FirewallWhitelist(new Whitelist()));

        $this->config('attack_blocker.enabled.ip', true);

        $this->config('attack_blocker.allowed_frequency.ip.requests', 2);
    }

    public function testBlacklist()
    {
        $this->blacklist->filter($this->request);

        $this->assertEquals('next', $this->blacklist->handle($this->request, $this->getNextClosure()));

        Firewall::blacklist('127.0.0.1');

        $this->assertInstanceOf(Response::class, $this->blacklist->handle($this->request, $this->getNextClosure()));
    }

    public function testWhitelist()
    {
        $this->whitelist->filter($this->request);

        $this->assertInstanceOf(Response::class, $this->whitelist->handle($this->request, $this->getNextClosure()));

        Firewall::whitelist('127.0.0.1');

        $this->assertEquals('next', $this->whitelist->handle($this->request, $this->getNextClosure()));
    }

    public function testBlockAttack()
    {
        $this->assertFalse(Firewall::isBeingAttacked('127.0.0.1'));

        $this->assertEquals('next', $this->blockAttacks->handle($this->request, $this->getNextClosure()));

        $this->assertTrue(Firewall::isBeingAttacked('127.0.0.1'));

        $this->assertInstanceOf(Response::class, $this->blockAttacks->handle($this->request, $this->getNextClosure()));
    }

    public function testRegister()
    {
        $this->assertInstanceOf(FirewallBlacklist::class, app('firewall.middleware.blacklist'));

        $this->assertInstanceOf(FirewallWhitelist::class, app('firewall.middleware.whitelist'));
    }
}
