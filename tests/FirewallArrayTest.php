<?php

namespace PragmaRX\Firewall\Tests;

use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class FirewallArrayTest extends FirewallTestCase
{
    /**
     * Get package.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        $this->config('use_database', false);

        return [
            FirewallServiceProvider::class,
        ];
    }
}
