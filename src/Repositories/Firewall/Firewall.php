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

use PragmaRX\Support\CacheManager;
use PragmaRX\Support\Config;

class Firewall implements FirewallInterface {

	private $model;

	private $cache;

	private $config;

	/**
	 * Create an instance of Message
	 *
	 * @param object $model
	 * @param Cache  $cache
	 */
	public function __construct($model, CacheManager $cache, Config $config)
	{
		$this->model = $model;

		$this->cache = $cache;

		$this->config = $config;
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

		if ($model = $this->model->where('ip_address', $ip)->first())
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
		if($ip = $this->find($ipAddress))
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
		return $this->model->all();
	}

	public function clear()
	{
		/**
		 * Deletes one by one to also remove them from cache
		 */
		foreach($this->all() as $ip)
		{
			$this->delete($ip['ip_address']);
		}
	}
}
