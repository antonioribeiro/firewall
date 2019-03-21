<?php

namespace PragmaRX\Firewall\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Firewall\Vendor\Laravel\ServiceProvider as FirewallServiceProvider;

class TestCase extends OrchestraTestCase
{
    protected $database;

    protected function config($key, $value = null)
    {
        if (!is_null($value)) {
            app()->config->set("firewall.{$key}", $value);
        }

        app()->config->get("firewall.{$key}");
    }

    private function configureDatabase()
    {
        if (!file_exists($path = __DIR__.'/databases')) {
            mkdir($path);
        }

        touch($this->database = tempnam($path, 'database.sqlite.'));

        app()->config->set(
            'database.connections.testbench',
            [
                'driver'   => 'sqlite',
                'database' => $this->database,
                'prefix'   => '',
            ]
        );
    }

    private function deleteDatabase()
    {
        @unlink($this->database);
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (config('firewall.enabled')) {
            $this->firewall = app('firewall');

            app('firewall.cache')->flush();
        }

        $this->configureDatabase();

        $this->artisan('migrate:refresh', ['--database' => 'testbench']);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->deleteDatabase();
    }

    protected function getPackageProviders($app)
    {
        $app['config']->set('firewall.enabled', true);

        $app['config']->set('firewall.geoip_database_path', __DIR__.'/geoipdb');

        $app['config']->set('firewall.enable_country_search', true);

        $app['config']->set('firewall.cache_expire_time', 10);

        return [
            FirewallServiceProvider::class,
        ];
    }
}
