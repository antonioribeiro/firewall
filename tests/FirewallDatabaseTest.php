<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class FirewallDatabaseTest extends FirewallTestCase
{
    protected function getPackageProviders($app)
    {
        $this->config('use_database', true);

        return [
            FirewallServiceProvider::class,
        ];
    }
}
