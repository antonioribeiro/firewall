<?php namespace PragmaRX\Firewall\Repositories\Cache;
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

class Cache implements CacheInterface {

	private $memory = array();

	/**
	 * Get the cache value
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return isset($this->memory[$key])
		             ? unserialize($this->memory[$key])
		             : null;
	}

	/**
	 * Insert or replace a value for a given key
	 *
	 * @param  string  $key
	 * @param  mixed  $value
	 * @param  integer $minutes
	 * @return mixed
	 */
	public function put($key, $value, $minutes = 0)
	{
		return $this->memory[$key] = serialize($value);
	}

	/**
	 * Increment is not supported
	 */
	public function increment($key, $value = 1)
	{
		throw new \Exception("Increment operations not supported by this driver.");
	}

	/**
	 * Decrement is not supported
	 */
	public function decrement($key, $value = 1)
	{
		throw new \Exception("Decrement operations not supported by this driver.");
	}

	/**
	 * Insert or replace a value for a key and remember is forever
	 *
	 * @param  string $key
	 * @param  mixed $value
	 * @return void
	 */
	public function forever($key, $value)
	{
		$this->put($key, $value);
	}

	/**
	 * Forget a key
	 *
	 * @param  string $key
	 * @return void
	 */
	public function forget($key)
	{
		unset($this->memory[$key]);
	}

	/**
	 * Erase the whole cache
	 *
	 * @return void
	 */
	public function flush()
	{
		$this->memory = array();
	}

	/**
	 * Get the cache Prefix,
	 *   returns an empty string for backward compatility with Interface
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return '';
	}

}