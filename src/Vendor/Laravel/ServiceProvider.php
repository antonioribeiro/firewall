<?php

namespace PragmaRX\Firewall\Vendor\Laravel;

use PragmaRX\Firewall\Database\Migrator;
use PragmaRX\Firewall\Exceptions\ConfigurationOptionNotAvailable;
use PragmaRX\Firewall\Filters\Blacklist;
use PragmaRX\Firewall\Filters\Whitelist;
use PragmaRX\Firewall\Firewall;
use PragmaRX\Firewall\Middleware\FirewallBlacklist;
use PragmaRX\Firewall\Middleware\FirewallWhitelist;
use PragmaRX\Firewall\Repositories\Countries;
use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\Firewall\Firewall as FirewallRepository;
use PragmaRX\Firewall\Support\AttackBlocker;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Blacklist as BlacklistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Clear as ClearCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Remove as RemoveCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Report as ReportCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Tables as TablesCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\UpdateGeoIp as UpdateGeoIpCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Whitelist as WhitelistCommand;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Filesystem;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Support\Response;
use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;

class ServiceProvider extends PragmaRXServiceProvider
{
    protected $packageVendor = 'pragmarx';

    protected $packageVendorCapitalized = 'PragmaRX';

    protected $packageName = 'firewall';

    protected $packageNameCapitalized = 'Firewall';

    private $firewall;

    /**
     * Return a proper response for blocked access.
     *
     * @return Response
     */
    public function blockAccess()
    {
        return $this->app['firewall']->blockAccess();
    }

    /**
     * Get the full path of the stub config file.
     *
     * @throws ConfigurationOptionNotAvailable
     *
     * @return string
     */
    private function getFirewallModel()
    {
        if (!$firewallModel = $this->getConfig('firewall_model')) {
            throw new ConfigurationOptionNotAvailable('Config option "firewall_model" is not available, please publish/check your configuration.');
        }

        return new $firewallModel();
    }

    /**
     * Get the current package directory.
     *
     * @return string
     */
    public function getPackageDir()
    {
        return __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'..';
    }

    /**
     * Get the root directory for this ServiceProvider.
     *
     * @return string
     */
    public function getRootDirectory()
    {
        return __DIR__.'/../..';
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['firewall'];
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        parent::register();

        $this->registerFileSystem();

        $this->registerCache();

        $this->registerFirewall();

        $this->registerDataRepository();

        $this->registerMigrator();

        $this->registerGeoIp();

        $this->registerAttackBlocker();

        $this->registerReportCommand();

        $this->registerTablesCommand();

        if ($this->getConfig('use_database')) {
            $this->registerWhitelistCommand();
            $this->registerBlacklistCommand();
            $this->registerRemoveCommand();
            $this->registerClearCommand();
        }

        $this->registerUpdateGeoIpCommand();

        $this->registerMiddleware();
    }

    /**
     * Register the attack blocker.
     */
    private function registerAttackBlocker()
    {
        $this->app->singleton('firewall.attackBlocker', function ($app) {
            return new AttackBlocker(
                $app['firewall.config'],
                $app['firewall.cache']
            );
        });
    }

    /**
     * Register the Blacklist Artisan command.
     *
     * @return void
     */
    private function registerBlacklistCommand()
    {
        $this->app->singleton('firewall.blacklist.command', function ($app) {
            return new BlacklistCommand();
        });

        $this->commands('firewall.blacklist.command');
    }

    /**
     * Register the Cache driver used by Firewall.
     *
     * @return void
     */
    private function registerCache()
    {
        $this->app->singleton('firewall.cache', function ($app) {
            return new CacheManager($app);
        });
    }

    /**
     * Register the List Artisan command.
     *
     * @return void
     */
    private function registerClearCommand()
    {
        $this->app->singleton('firewall.clear.command', function ($app) {
            return new ClearCommand();
        });

        $this->commands('firewall.clear.command');
    }

    /**
     * Register the Data Repository driver used by Firewall.
     *
     * @return void
     */
    private function registerDataRepository()
    {
        $this->app->singleton('firewall.dataRepository', function ($app) {
            return new DataRepository(
                new FirewallRepository(
                    $this->getFirewallModel(),
                    $app['firewall.cache'],
                    $app['firewall.config'],
                    $app['firewall.fileSystem']
                ),

                $app['firewall.config'],

                $app['firewall.cache'],

                $app['firewall.fileSystem'],

                new Countries()
            );
        });
    }

    /**
     * Register the Filesystem driver used by Firewall.
     *
     * @return void
     */
    private function registerFileSystem()
    {
        $this->app->singleton('firewall.fileSystem', function ($app) {
            return new Filesystem();
        });
    }

    /**
     * Takes all the components of Firewall and glues them
     * together to create Firewall.
     *
     * @return void
     */
    private function registerFirewall()
    {
        $this->app->singleton('firewall', function ($app) {
            $app['firewall.loaded'] = true;

            $this->firewall = new Firewall(
                $app['firewall.config'],
                $app['firewall.dataRepository'],
                $app['firewall.cache'],
                $app['firewall.fileSystem'],
                $app['request'],
                $app['firewall.migrator'],
                $app['firewall.geoip'],
                $attackBlocker = $app['firewall.attackBlocker']
            );

            $attackBlocker->setFirewall($this->firewall);

            return $this->firewall;
        });
    }

    /**
     * Register blocking and unblocking Middleware.
     *
     * @return void
     */
    private function registerMiddleware()
    {
        $this->app->singleton('firewall.middleware.blacklist', function ($app) {
            return new FirewallBlacklist(new Blacklist());
        });

        $this->app->singleton('firewall.middleware.whitelist', function ($app) {
            return new FirewallWhitelist(new Whitelist());
        });
    }

    private function registerMigrator()
    {
        $this->app->singleton('firewall.migrator', function ($app) {
            $connection = $this->getConfig('connection');

            return new Migrator($app['db'], $connection);
        }
        );
    }

    private function registerGeoIp()
    {
        $this->app->singleton('firewall.geoip', function () {
            return new GeoIp($this->getConfig('geoip_database_path'));
        });
    }

    /**
     * Register the List Artisan command.
     *
     * @return void
     */
    private function registerRemoveCommand()
    {
        $this->app->singleton('firewall.remove.command', function ($app) {
            return new RemoveCommand();
        });

        $this->commands('firewall.remove.command');
    }

    /**
     * Register the List Artisan command.
     *
     * @return void
     */
    private function registerReportCommand()
    {
        $this->app->singleton('firewall.list.command', function ($app) {
            return new ReportCommand();
        });

        $this->commands('firewall.list.command');
    }

    private function registerTablesCommand()
    {
        $this->app->singleton('firewall.tables.command', function ($app) {
            return new TablesCommand();
        });

        $this->commands('firewall.tables.command');
    }

    /**
     * Register the updategeoip command.
     */
    private function registerUpdateGeoIpCommand()
    {
        $this->app->singleton('firewall.updategeoip.command', function ($app) {
            return new UpdateGeoIpCommand();
        });

        $this->commands('firewall.updategeoip.command');
    }

    /**
     * Register the Whitelist Artisan command.
     *
     * @return void
     */
    private function registerWhitelistCommand()
    {
        $this->app->singleton('firewall.whitelist.command', function ($app) {
            return new WhitelistCommand();
        });

        $this->commands('firewall.whitelist.command');
    }
}
