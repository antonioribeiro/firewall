<?php

namespace PragmaRX\Firewall\Vendor\Laravel;

use PragmaRX\Support\Response;
use PragmaRX\Firewall\Firewall;
use PragmaRX\Support\Filesystem;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Firewall\Database\Migrator;
use PragmaRX\Firewall\Filters\Blacklist;
use PragmaRX\Firewall\Filters\Whitelist;
use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Middleware\FirewallBlacklist;
use PragmaRX\Firewall\Middleware\FirewallWhitelist;
use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;
use PragmaRX\Firewall\Exceptions\ConfigurationOptionNotAvailable;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Whitelist as WhitelistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Blacklist as BlacklistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Report as ReportCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Remove as RemoveCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Clear as ClearCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Tables as TablesCommand;
use PragmaRX\Firewall\Repositories\Firewall\Firewall as FirewallRepository;

class ServiceProvider extends PragmaRXServiceProvider
{
    protected $packageVendor = 'pragmarx';

    protected $packageVendorCapitalized = 'PragmaRX';

    protected $packageName = 'firewall';

    protected $packageNameCapitalized = 'Firewall';

    /**
     * Return a proper response for blocked access
     *
     * @return Response
     */
    public function blockAccess($content = null, $status = null) {
        return $this->app['firewall']->blockAccess($content, $status);
    }

    /**
     * Get the full path of the stub config file.
     * @return string
     * @throws ConfigurationOptionNotAvailable
     */
    private function getFirewallModel() {
        if (!$firewallModel = $this->getConfig('firewall_model')) {
            throw new ConfigurationOptionNotAvailable('Config option "firewall_model" is not available, please publish/check your configuration.');
        }

        return new $firewallModel;
    }

    /**
     * Get the current package directory.
     *
     * @return string
     */
    public function getPackageDir() {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..';
    }

    /**
     * Get the root directory for this ServiceProvider
     *
     * @return string
     */
    public function getRootDirectory() {
        return __DIR__ . '/../..';
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return ['firewall'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        parent::register();

        $this->registerFileSystem();

        $this->registerCache();

        $this->registerFirewall();

        $this->registerDataRepository();

        $this->registerMigrator();

        $this->registerReportCommand();

        $this->registerTablesCommand();

        if ($this->getConfig('use_database')) {
            $this->registerWhitelistCommand();
            $this->registerBlacklistCommand();
            $this->registerRemoveCommand();
            $this->registerClearCommand();
        }

        $this->registerMiddleware();
    }

    /**
     * Register the Blacklist Artisan command
     *
     * @return void
     */
    private function registerBlacklistCommand() {
        $this->app['firewall.blacklist.command'] = $this->app->share(function ($app) {
            return new BlacklistCommand;
        });

        $this->commands('firewall.blacklist.command');
    }

    /**
     * Register the Cache driver used by Firewall
     *
     * @return void
     */
    private function registerCache() {
        $this->app['firewall.cache'] = $this->app->share(function ($app) {
            return new CacheManager($app);
        });
    }

    /**
     * Register the List Artisan command
     *
     * @return void
     */
    private function registerClearCommand() {
        $this->app['firewall.clear.command'] = $this->app->share(function ($app) {
            return new ClearCommand;
        });

        $this->commands('firewall.clear.command');
    }

    /**
     * Register the Data Repository driver used by Firewall
     *
     * @return void
     */
    private function registerDataRepository() {
        $this->app['firewall.dataRepository'] = $this->app->share(function ($app) {
            return new DataRepository(
                new FirewallRepository(
                    $this->getFirewallModel(),
                    $app['firewall.cache'],
                    $app['firewall.config'],
                    $app['firewall.fileSystem']
                ),

                $app['firewall.config'],

                $app['firewall.cache'],

                $app['firewall.fileSystem']
            );
        });
    }

    /**
     * Register the Filesystem driver used by Firewall
     *
     * @return void
     */
    private function registerFileSystem() {
        $this->app['firewall.fileSystem'] = $this->app->share(function ($app) {
            return new Filesystem;
        });
    }

    /**
     * Takes all the components of Firewall and glues them
     * together to create Firewall.
     *
     * @return void
     */
    private function registerFirewall() {
        $this->app['firewall'] = $this->app->share(function ($app) {
            $app['firewall.loaded'] = true;

            return new Firewall(
                $app['firewall.config'],
                $app['firewall.dataRepository'],
                $app['firewall.cache'],
                $app['firewall.fileSystem'],
                $app['request'],
                $app['firewall.migrator'],
                new GeoIp()
            );
        });
    }

    /**
     * Register blocking and unblocking Middleware
     *
     * @return void
     */
    private function registerMiddleware() {
        $this->app['firewall.middleware.blacklist'] = $this->app->share(function ($app) {
            return new FirewallBlacklist(new Blacklist());
        });
        $this->app['firewall.middleware.whitelist'] = $this->app->share(function ($app) {
            return new FirewallWhitelist(new Whitelist());
        });
    }

    private function registerMigrator() {
        $this->app['firewall.migrator'] = $this->app->share(
            function ($app) {
                $connection = $this->getConfig('connection');

                return new Migrator($app['db'], $connection);
            }
        );
    }

    /**
     * Register the List Artisan command
     *
     * @return void
     */
    private function registerRemoveCommand() {
        $this->app['firewall.remove.command'] = $this->app->share(function ($app) {
            return new RemoveCommand;
        });

        $this->commands('firewall.remove.command');
    }

    /**
     * Register the List Artisan command
     *
     * @return void
     */
    private function registerReportCommand() {
        $this->app['firewall.list.command'] = $this->app->share(function ($app) {
            return new ReportCommand;
        });

        $this->commands('firewall.list.command');
    }

    private function registerTablesCommand() {
        $this->app['firewall.tables.command'] = $this->app->share(function () {
            return new TablesCommand;
        });

        $this->commands('firewall.tables.command');
    }

    /**
     * Register the Whitelist Artisan command
     *
     * @return void
     */
    private function registerWhitelistCommand() {
        $this->app['firewall.whitelist.command'] = $this->app->share(function ($app) {
            return new WhitelistCommand;
        });

        $this->commands('firewall.whitelist.command');
    }
}
