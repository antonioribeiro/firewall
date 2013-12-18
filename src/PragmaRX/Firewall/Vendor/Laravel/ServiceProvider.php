<?php namespace PragmaRX\Firewall\Vendor\Laravel;

use PragmaRX\Firewall\Firewall;

use PragmaRX\Firewall\Support\Config;
use PragmaRX\Firewall\Support\Filesystem;
use PragmaRX\Firewall\Support\CacheManager;

use PragmaRX\Firewall\Vendor\Laravel\Artisan\Whitelist as WhitelistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Blacklist as BlacklistCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Report as ReportCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Remove as RemoveCommand;
use PragmaRX\Firewall\Vendor\Laravel\Artisan\Clear as ClearCommand;

use PragmaRX\Firewall\Repositories\DataRepository;
use PragmaRX\Firewall\Repositories\Cache\Cache;
use PragmaRX\Firewall\Repositories\Firewall\Firewall as FirewallRepository;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Illuminate\Foundation\AliasLoader as IlluminateAliasLoader;

class ServiceProvider extends IlluminateServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('pragmarx/firewall', 'pragmarx/firewall', __DIR__.'/../../../..');

		if( $this->getConfig('create_firewall_alias') )
		{
			IlluminateAliasLoader::getInstance()->alias(
															$this->getConfig('firewall_alias'), 
															'PragmaRX\Firewall\Vendor\Laravel\Facades\Firewall'
														);
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerFileSystem();

		$this->registerConfig();

		$this->registerCache();

		$this->registerFirewall();

		$this->registerDataRepository();

		$this->registerWhitelistCommand();
		$this->registerBlacklistCommand();
		$this->registerReportCommand();
		$this->registerRemoveCommand();
		$this->registerClearCommand();

		$this->commands('firewall.whitelist.command');
		$this->commands('firewall.blacklist.command');
		$this->commands('firewall.list.command');
		$this->commands('firewall.remove.command');
		$this->commands('firewall.clear.command');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
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
	 * Register the Config driver used by Firewall
	 * 
	 * @return void
	 */
	private function registerConfig()
	{
		$this->app['firewall.config'] = $this->app->share(function($app)
		{
			return new Config($app['firewall.fileSystem'], $app);
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
			$firewallModel = $this->getConfig('firewall_model');

			return new DataRepository(
										new FirewallRepository(new $firewallModel, $this->app['firewall.cache']),

										$this->app['firewall.config'],

										$this->app['firewall.cache'],

										$this->app['firewall.fileSystem']
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
									$app['firewall.fileSystem']
								);
		});
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
	}

	/**
	 * Helper function to ease the use of configurations
	 * 
	 * @param  string $key configuration key
	 * @return string      configuration value
	 */
	public function getConfig($key)
	{
		return $this->app['config']["pragmarx/firewall::$key"];
	}
}
