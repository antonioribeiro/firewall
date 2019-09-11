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
use PragmaRX\Firewall\Repositories\Cache\Cache;
use PragmaRX\Firewall\Repositories\Countries;
use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\IpList;
use PragmaRX\Firewall\Repositories\Message;
use PragmaRX\Firewall\Support\AttackBlocker;
use PragmaRX\Firewall\Support\IpAddress;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Blacklist as BlacklistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Clear as ClearCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Flush;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Remove as RemoveCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Report as ReportCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\UpdateGeoIp as UpdateGeoIpCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Whitelist as WhitelistCommand;
use PragmaRX\Support\Filesystem;
use PragmaRX\Support\GeoIp\GeoIp;
use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;

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
     * @return \Illuminate\Database\Eloquent\Model
     */
    private function getFirewallModel()
    {
        if (!$firewallModel = $this->getConfig('firewall_model')) {
            throw new ConfigurationOptionNotAvailable('Config option "firewall_model" is not available, please publish/check your configuration.');
        }

        return new $firewallModel();
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

        $this->registerFileSystem();

        $this->registerCache();

        $this->registerFirewall();

        $this->registerDataRepository();

        $this->registerMessageRepository();

        $this->registerIpList();

        $this->registerIpAddress();

        $this->registerGeoIp();

        $this->registerAttackBlocker();

        $this->registerReportCommand();

        $this->registerCountriesRepository();

        if ($this->getConfig('use_database')) {
            $this->registerMigrations();
            $this->registerWhitelistCommand();
            $this->registerBlacklistCommand();
            $this->registerRemoveCommand();
            $this->registerClearCommand();
        }

        $this->registerUpdateGeoIpCommand();

        // $this->registerFlushCommand(); // TODO

        $this->registerMiddleware();

        $this->registerEventListeners();
    }

    /**
     * Register the attack blocker.
     */
    private function registerAttackBlocker()
    {
        $this->app->singleton('firewall.attackBlocker', function () {
            return new AttackBlocker();
        });
    }

    /**
     * Register the countries repository.
     */
    private function registerCountriesRepository()
    {
        $this->app->singleton('firewall.countries', function () {
            return new Countries();
        });
    }

    /**
     * Register the Blacklist Artisan command.
     *
     * @return void
     */
    private function registerBlacklistCommand()
    {
        $this->app->singleton('firewall.blacklist.command', function () {
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
        $this->app->singleton('firewall.cache', function () {
            return new Cache(app('cache'));
        });
    }

    /**
     * Register the List Artisan command.
     *
     * @return void
     */
    private function registerClearCommand()
    {
        $this->app->singleton('firewall.clear.command', function () {
            return new ClearCommand();
        });

        $this->commands('firewall.clear.command');
    }

    /**
     * Register the cache:clear Artisan command.
     *
     * @return void
     */
    private function registerFlushCommand()
    {
        $this->app->singleton('firewall.flush.command', function () {
            return new Flush();
        });

        $this->commands('firewall.flush.command');
    }

    /**
     * Register the Data Repository driver used by Firewall.
     *
     * @return void
     */
    private function registerDataRepository()
    {
        $this->app->singleton('firewall.datarepository', function () {
            return new DataRepository();
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
        $this->app->singleton('firewall.filesystem', function () {
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
                $app['firewall.datarepository'],
                $app['request'],
                $attackBlocker = $app['firewall.attackBlocker'],
                $app['firewall.messages']
            );

            $attackBlocker->setFirewall($this->firewall);

            return $this->firewall;
        });
    }

    private function registerIpAddress()
    {
        $this->app->singleton('firewall.ipaddress', function () {
            return new IpAddress();
        });
    }

    /**
     * Register the ip list repository.
     */
    private function registerIpList()
    {
        $this->app->singleton('firewall.iplist', function () {
            return new IpList($this->getFirewallModel());
        });
    }

    /**
     * Register the message repository.
     */
    private function registerMessageRepository()
    {
        $this->app->singleton('firewall.messages', function () {
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
        $this->app->singleton('firewall.middleware.blacklist', function () {
            return new FirewallBlacklist(new Blacklist());
        });

        $this->app->singleton('firewall.middleware.whitelist', function () {
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
        $this->app->singleton('firewall.remove.command', function () {
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
        $this->app->singleton('firewall.list.command', function () {
            return new ReportCommand();
        });

        $this->commands('firewall.list.command');
    }

    /**
     * Register the updategeoip command.
     */
    private function registerUpdateGeoIpCommand()
    {
        $this->app->singleton('firewall.updategeoip.command', function () {
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
        $this->app->singleton('firewall.whitelist.command', function () {
            return new WhitelistCommand();
        });

        $this->commands('firewall.whitelist.command');
    }
}
