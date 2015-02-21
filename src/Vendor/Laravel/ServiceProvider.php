<?php

namespace PragmaRX\Firewall\Vendor\Laravel;

use PragmaRX\Firewall\Database\Migrator;
use PragmaRX\Firewall\Exceptions\ConfigurationOptionNotAvailable;
use PragmaRX\Firewall\Firewall;

use PragmaRX\Support\Filesystem;
use PragmaRX\Support\CacheManager;
use PragmaRX\Support\GeoIp;
use PragmaRX\Support\Response;

use PragmaRX\Firewall\Vendor\Laravel\Artisan\Whitelist as WhitelistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Blacklist as BlacklistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Report as ReportCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Remove as RemoveCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Clear as ClearCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Tables as TablesCommand;

use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\Firewall\Firewall as FirewallRepository;

use PragmaRX\Support\ServiceProvider as PragmaRXServiceProvider;

class ServiceProvider extends PragmaRXServiceProvider {

    protected $packageVendor = 'pragmarx';

    protected $packageVendorCapitalized = 'PragmaRX';

    protected $packageName = 'firewall';

    protected $packageNameCapitalized = 'Firewall';

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

        $this->registerReportCommand();

        $this->registerTablesCommand();

        if ($this->getConfig('use_database'))
        {
            $this->registerWhitelistCommand();
            $this->registerBlacklistCommand();
            $this->registerRemoveCommand();
            $this->registerClearCommand();
        }

        $this->registerFilters();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('firewall');
    }

    /**
     * Register the Filesystem driver used by Firewall
     * 
     * @return void
     */
    private function registerFileSystem()
    {
        $this->app['firewall.fileSystem'] = $this->app->share(function($app)
        {
            return new Filesystem;
        });
    }

    /**
     * Register the Cache driver used by Firewall
     * 
     * @return void
     */
    private function registerCache()
    {
        $this->app['firewall.cache'] = $this->app->share(function($app)
        {
            return new CacheManager($app);
        });
    }

    /**
     * Register the Data Repository driver used by Firewall
     * 
     * @return void
     */
    private function registerDataRepository()
    {
        $this->app['firewall.dataRepository'] = $this->app->share(function($app)
        {
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
     * Takes all the components of Firewall and glues them
     * together to create Firewall.
     *
     * @return void
     */
    private function registerFirewall()
    {
        $this->app['firewall'] = $this->app->share(function($app)
        {
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
     * Register blocking and unblocking filters
     * 
     * @return void
     */
    private function registerFilters()
    {
        $this->app['router']->filter('fw-block-bl', '\PragmaRX\Firewall\Filters\Blacklist');

        $this->app['router']->filter('fw-allow-wl', '\PragmaRX\Firewall\Filters\Whitelist');
    }

    /**
     * Return a proper response for blocked access
     *
     * @return Response
     */ 
    private function blockAccess($content = null, $status = null)
    {
        return $this->app['firewall']->blockAccess($content, $status);
    }

    /**
     * Register the Whitelist Artisan command
     *
     * @return void
     */ 
    private function registerWhitelistCommand()
    {
        $this->app['firewall.whitelist.command'] = $this->app->share(function($app)
        {
            return new WhitelistCommand;
        });

        $this->commands('firewall.whitelist.command');
    }

    /**
     * Register the Blacklist Artisan command
     *
     * @return void
     */ 
    private function registerBlacklistCommand()
    {
        $this->app['firewall.blacklist.command'] = $this->app->share(function($app)
        {
            return new BlacklistCommand;
        });

        $this->commands('firewall.blacklist.command');
    }

    /**
     * Register the List Artisan command
     *
     * @return void
     */ 
    private function registerReportCommand()
    {
        $this->app['firewall.list.command'] = $this->app->share(function($app)
        {
            return new ReportCommand;
        });

        $this->commands('firewall.list.command');
    }

    /**
     * Register the List Artisan command
     *
     * @return void
     */ 
    private function registerRemoveCommand()
    {
        $this->app['firewall.remove.command'] = $this->app->share(function($app)
        {
            return new RemoveCommand;
        });

        $this->commands('firewall.remove.command');
    }

    /**
     * Register the List Artisan command
     *
     * @return void
     */ 
    private function registerClearCommand()
    {
        $this->app['firewall.clear.command'] = $this->app->share(function($app)
        {
            return new ClearCommand;
        });

        $this->commands('firewall.clear.command');
    }

    /**
     * Get the root directory for this ServiceProvider
     * 
     * @return string
     */
    public function getRootDirectory()
    {
        return __DIR__.'/../..';
    }

	private function registerTablesCommand()
	{
		$this->app['firewall.tables.command'] = $this->app->share(function()
		{
			return new TablesCommand;
		});

        $this->commands('firewall.tables.command');
	}

	private function registerMigrator()
	{
		$this->app['firewall.migrator'] = $this->app->share(
			function($app)
			{
				$connection = $this->getConfig('connection');

				return new Migrator($app['db'], $connection);
			}
		);
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
     * Get the full path of the stub config file.
     *
     * @return string
     */
    private function getFirewallModel()
    {
        if ( ! $firewallModel = $this->getConfig('firewall_model'))
        {
            throw new ConfigurationOptionNotAvailable('Config option "firewall_model" is not available, please publish/check your configuration.');
        }

        return new $firewallModel;
    }

}
