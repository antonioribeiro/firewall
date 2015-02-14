<?php namespace PragmaRX\Firewall\Repositories\Firewall;
/**
 * Part of the Firewall package.
 *
 * NOTICE OF LICENSE
 *
 * Licensed under the 3-clause BSD License.
 *
 * This source file is subject to the 3-clause BSD License that is
 * bundled with this package in the LICENSE file.  It is also available at
 * the following URL: http://www.opensource.org/licenses/BSD-3-Clause
 *
 * @package    Firewall
 * @author     Antonio Carlos Ribeiro @ PragmaRX
 * @license    BSD License (3-clause)
 * @copyright  (c) 2013, PragmaRX
 * @link       http://pragmarx.com
 */

use PragmaRX\Support\Config;
use PragmaRX\Support\CacheManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;

class Firewall implements FirewallInterface {

	/**
	 * @var object
	 */
	private $model;

	/**
	 * @var Cache|CacheManager
	 */
	private $cache;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var Filesystem
	 */
	private $fileSystem;

	/**
	 * Create an instance of Message
	 *
	 * @param object $model
	 * @param Cache|CacheManager $cache
	 * @param Config $config
	 * @param Filesystem $fileSystem
	 */
	public function __construct($model, CacheManager $cache, Config $config, Filesystem $fileSystem)
	{
		$this->model = $model;

		$this->cache = $cache;

		$this->config = $config;

		$this->fileSystem = $fileSystem;
	}

	/**
	 * Find a Ip in the data source
	 *
	 * @param  string $ip
	 * @return object|null
	 */
	public function find($ip)
	{
		if ($this->cacheHas($ip))
		{
			return $this->cacheGet($ip);
		}

		if ($model = $this->databaseAndConfigFind($ip))
		{
			$this->cacheRemember($model);
		}

		return $model;
	}

	/**
	 * Find a Ip in the data source
	 *
	 * @param  string $ip
	 * @return object|null
	 */
	public function addToList($whitelist, $ip)
	{
		$this->model->unguard();

		$model = $this->model->create(array(
										'ip_address' => $ip,
										'whitelisted' => $whitelist
									));

		$this->cacheRemember($model);

		return $model;
	}

	public function delete($ipAddress)
	{
		if ($ip = $this->find($ipAddress))
		{
			$ip->delete();

			$this->cacheForget($ipAddress);

			return true;
		}

		return false;
	}

	public function cacheKey($ip)
	{
		return "firewall.ip_address.$ip";
	}

	public function cacheHas($ip)
	{
		return $this->cache->has($this->cacheKey($ip));
	}

	public function cacheGet($ip)
	{
		return $this->cache->get($this->cacheKey($ip));
	}

	public function cacheForget($ip)
	{
		$this->cache->forget($this->cacheKey($ip));
	}

	public function cacheRemember($model)
	{
		$this->cache->put($this->cacheKey($model->ip_address), $model, $this->config->get('cache_expire_time',10));
	}

	public function all()
	{
		if ($this->config->get('use_database'))
		{
			$database_ips = $this->model->all();
		}
		else
		{
			$database_ips = array();
		}

		$config_ips = $this->toModels($this->getNonDatabaseIps());

		return $this->toCollection(array_merge((array) $database_ips, $config_ips));
	}

	public function clear()
	{
		/**
		 * Deletes one by one to also remove them from cache
		 */
		foreach ($this->all() as $ip)
		{
			$this->delete($ip['ip_address']);
		}
	}

	private function databaseAndConfigFind($ip)
	{
		if ($model = $this->nonDatabaseFind($ip))
		{
			return $model;
		}

		if ($this->config->get('use_database'))
		{
			return $this->model->where('ip_address', $ip)->first();
		}

		return null;
	}

	private function nonDatabaseFind($ip)
	{
		$ips = $this->getNonDatabaseIps();

		if ($ip = array_search($ip, $ips))
		{
			return $this->makeModel($ip);
		}

		return null;
	}

	private function getNonDatabaseIps()
	{
		return array_merge_recursive(
			array_map(function($ip) { $ip['whitelisted'] = true; return $ip; }, $this->toIpsArray($this->config->get('whitelisted_array'))),
			array_map(function($ip) { $ip['whitelisted'] = true; return $ip; }, $this->readFile($this->config->get('whitelisted_file'))),

			array_map(function($ip) { $ip['whitelisted'] = false; return $ip; }, $this->toIpsArray($this->config->get('blacklisted_array'))),
			array_map(function($ip) { $ip['whitelisted'] = false; return $ip; }, $this->readFile($this->config->get('blacklisted_file')))
		);
	}

	private function toModels($ipList)
	{
		$ips = array();

		foreach ($ipList as $ip)
		{
			$ips[] = $this->makeModel($ip);
		}

		return $ips;
	}

	/**
	 * @param $ip
	 * @return mixed
	 */
	private function makeModel($ip)
	{
		return $this->model->newInstance($ip);
	}

	private function readFile($file)
	{
		if ($this->fileSystem->exists($file))
		{
			$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			return $this->toIpsArray($lines);
		}

		return array();
	}

	private function toCollection($array)
	{
		return new Collection($array);
	}

	private function toIpsArray($list)
	{
		return array_map(function($ip)
		{
			return array('ip_address' => $ip);
		}, $list);
	}

}
