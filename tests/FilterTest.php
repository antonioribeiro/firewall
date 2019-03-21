<?php

namespace PragmaRX\Firewall\Tests;

use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use PragmaRX\Firewall\Filters\Blacklist;
use PragmaRX\Firewall\Filters\Whitelist;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

class FilterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Firewall::setIp('127.0.0.1');
    }

    public function testWhitelist()
    {
        Firewall::whitelist('127.0.0.1');

        $response = (new Whitelist())->filter();

        $this->assertNull($response);
    }

    public function testRedirectWhitelisted()
    {
        $this->config('responses.whitelist.redirect_to', 'http://whatever.com');

        $response = (new Whitelist())->filter();

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectWhitelistedToRouteName()
    {
        Route::get('/redirected', ['as' => 'redirected', function () {
            return 'whatever';
        }]);

        $this->config('responses.whitelist.redirect_to', 'redirected');

        $response = (new Whitelist())->filter();

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertTrue(str_contains($response->getContent(), '/redirected'));
    }

    public function testRedirectWhitelistedToView()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->config('responses.whitelist.view', 'redirected');

        $response = (new Whitelist())->filter();
    }

    public function testWhitelistIgnoreListing()
    {
        $this->config('responses.whitelist.code', 200);

        $response = (new Whitelist())->filter();

        $this->assertNull($response);
    }

    public function testRedirectWhitelistedToAbort()
    {
        $this->expectException(HttpException::class);

        $this->config('responses.whitelist.abort', true);

        $response = (new Whitelist())->filter();
    }

    public function testNotWhitelisted()
    {
        $response = (new Whitelist())->filter();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testBlacklist()
    {
        Firewall::blacklist('127.0.0.1');

        $response = (new Blacklist())->filter();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testNotBlacklisted()
    {
        $response = (new Blacklist())->filter();

        $this->assertNull($response);
    }
}
