<?php

namespace PragmaRX\Firewall\Vendor\Laravel;

use Illuminate\Support\Facades\Event;
use PragmaRX\Firewall\Events\AttackDetected;
use PragmaRX\Firewall\Exceptions\ConfigurationOptionNotAvailable;
use PragmaRX\Firewall\Filters\Blacklist;
use PragmaRX\Firewall\Filters\Whitelist;
use PragmaRX\Firewall\Firewall;
use PragmaRX\Firewall\Listeners\NotifyAdmins;
use PragmaRX\Firewall\Middleware\FirewallBlacklist;
use PragmaRX\Firewall\Middleware\FirewallWhitelist;
use PragmaRX\Firewall\Repositories\Countries;
use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\Message;
use PragmaRX\Firewall\Support\AttackBlocker;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Blacklist as BlacklistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Clear as ClearCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Remove as RemoveCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Report as ReportCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\UpdateGeoIp as UpdateGeoIpCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Whitelist as WhitelistCommand;
use Illuminate\Cache\CacheManager;
use PragmaRX\Support\Filesystem;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;
use PragmaRX\Firewall\Repositories\Cache\Cache;

class ServiceProvider extends PragmaRXServiceProvider
{
    protected $packageVendor = 'pragmarx';

    protected $packageVendorCapitalized = 'PragmaRX';

    protected $packageName = 'firewall';

    protected $packageNameCapitalized = 'Firewall';

    private $firewall;

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

        if (!$this->getConfig('enabled')) {
            return;
        }

        $this->registerMigrations();

        $this->registerFileSystem();

        $this->registerCache();

        $this->registerFirewall();

        $this->registerDataRepository();

        $this->registerMessageRepository();

        $this->registerGeoIp();

        $this->registerAttackBlocker();

        $this->registerReportCommand();

        if ($this->getConfig('use_database')) {
            $this->registerWhitelistCommand();
            $this->registerBlacklistCommand();
            $this->registerRemoveCommand();
            $this->registerClearCommand();
        }

        $this->registerUpdateGeoIpCommand();

        $this->registerMiddleware();

        $this->registerEventListeners();
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
            return new Cache($app['firewall.config'], app('cache'));
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
                $this->getFirewallModel(),

                $app['firewall.config'],

                $app['firewall.cache'],

                $app['firewall.fileSystem'],

                new Countries($app['firewall.geoip']),

                $app['firewall.message']
            );
        });
    }

    /**
     * Register event listeners.
     */
    private function registerEventListeners()
    {
        Event::listen(AttackDetected::class, NotifyAdmins::class);
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
                $app['request'],
                $attackBlocker = $app['firewall.attackBlocker'],
                $app['firewall.message']
            );

            $attackBlocker->setFirewall($this->firewall);

            return $this->firewall;
        });
    }

    /**
     * Register the message repository.
     */
    private function registerMessageRepository()
    {
        $this->app->singleton('firewall.message', function ($app) {
            return new Message();
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

    private function registerMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../../migrations');
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
