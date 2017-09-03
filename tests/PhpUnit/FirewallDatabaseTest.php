<?php

namespace PragmaRX\Firewall\Tests\PhpUnit;

use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class FirewallDatabaseTest extends FirewallTestCase
{
    protected function getPackageProviders($app)
    {
        $this->setConfig('use_database', true);

        return [
            FirewallServiceProvider::class,
        ];
    }
}
