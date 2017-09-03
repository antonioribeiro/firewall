<?php

namespace PragmaRX\Firewall\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected function setConfig($key, $value)
    {
        app()->config->set("firewall.{$key}", $value);
    }

    private function configureDatabase()
    {
        touch($database = __DIR__.'/database.sqlite');

        app()->config->set(
            'database.connections.testbench',
            [
                'driver'   => 'sqlite',
                'database' => $database,
                'prefix'   => '',
            ]
        );
    }

    protected function setUp()
    {
        parent::setUp();

        $this->configureDatabase();

        $this->artisan('migrate:refresh', ['--database' => 'testbench']);
    }

    protected function getPackageProviders($app)
    {
        $app['config']->set('firewall.enabled', true);

        $app['config']->set('firewall.geoip_database_path', __DIR__.'/geoipdb');

        $app['config']->set('firewall.enable_country_search', true);

        return [
            FirewallServiceProvider::class,
        ];
    }
}
