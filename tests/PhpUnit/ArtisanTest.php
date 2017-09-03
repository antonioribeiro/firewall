<?php

namespace PragmaRX\Firewall\Tests\PhpUnit;

use Artisan;

class ArtisanTest extends TestCase
{
    public function test_blacklist()
    {
        Artisan::call('firewall:updategeoip');
    }

    public function test_report()
    {
        Artisan::call('firewall:list');
    }
}
