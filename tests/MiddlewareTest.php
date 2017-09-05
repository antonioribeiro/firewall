<?php

namespace PragmaRX\Firewall\Tests;

use Route;
use InvalidArgumentException;
use Illuminate\Http\RedirectResponse;
use PragmaRX\Firewall\Filters\Whitelist;
use PragmaRX\Firewall\Filters\Blacklist;
use PragmaRX\Firewall\Vendor\Laravel\Facade as Firewall;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MiddlewareTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        Firewall::setIp('127.0.0.1');
    }

    public function test_whitelist()
    {
        Firewall::whitelist('127.0.0.1');

        $response = (new Whitelist())->filter();

        $this->assertNull($response);
    }

    public function test_redirect_whitelisted()
    {
        $this->config('responses.whitelist.redirect_to', 'http://whatever.com');

        $response = (new Whitelist())->filter();

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function test_redirect_whitelisted_to_route_name()
    {
        Route::get('/redirected', ['as' => 'redirected', function() {
            return 'whatever';
        }]);

        $this->config('responses.whitelist.redirect_to', 'redirected');

        $response = (new Whitelist())->filter();

        $this->assertInstanceOf(RedirectResponse::class, $response);

        $this->assertTrue(str_contains($response->getContent(), '/redirected'));
    }

    public function test_redirect_whitelisted_to_view()
    {
        $this->expectException(InvalidArgumentException::class);

        $this->config('responses.whitelist.view', 'redirected');

        $response = (new Whitelist())->filter();
    }

    public function test_whitelist_ignore_listing()
    {
        $this->config('responses.whitelist.code', 200);

        $response = (new Whitelist())->filter();

        $this->assertNull($response);
    }

    public function test_redirect_whitelisted_to_abort()
    {
        $this->expectException(HttpException::class);

        $this->config('responses.whitelist.abort', true);

        $response = (new Whitelist())->filter();
    }

    public function test_not_whitelisted()
    {
        $response = (new Whitelist())->filter();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_blacklist()
    {
        Firewall::blacklist('127.0.0.1');

        $response = (new Blacklist())->filter();

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_not_blacklisted()
    {
        $response = (new Blacklist())->filter();

        $this->assertNull($response);
    }
}
