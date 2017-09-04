<?php

namespace PragmaRX\Firewall\Repositories\Cache;

use Illuminate\Cache\CacheManager;
use PragmaRX\Support\Config;

class Cache implements CacheInterface
{
    const CACHE_BASE_NAME = 'firewall.';

    private $memory = [];

    private $cache;

    private $config;

    public function __construct(Config $config, CacheManager $cache)
    {
        $this->config = $config;

        $this->cache = $cache;
    }

    /**
     * Increment is not supported.
     */
    public function increment($key, $value = 1)
    {
        throw new \Exception('Increment operations not supported by this driver.');
    }

    /**
     * Decrement is not supported.
     */
    public function decrement($key, $value = 1)
    {
        throw new \Exception('Decrement operations not supported by this driver.');
    }

    /**
     * Insert or replace a value for a key and remember is forever.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function forever($key, $value)
    {
        if ($this->expireTime()) {
            $this->put($this->key($key), $value);
        }
    }

    /**
     * Cache remember.
     *
     * @param $model
     *
     * @return void
     */
    public function remember($model)
    {
        if ($timeout = $this->expireTime()) {
            $this->put($this->key($model->ip_address), $model, $timeout);
        }
    }

    /**
     * Make a cache key.
     *
     * @param $ip
     *
     * @return string
     */
    public function key($key)
    {
        return sha1(static::CACHE_BASE_NAME."ip_address.$key");
    }

    /**
     * Check if cache has key.
     *
     * @param $ip
     *
     * @return bool
     */
    public function has($key)
    {
        if ($this->expireTime()) {
            return $this->cache->has($this->key($key));
        }

        return false;
    }

    /**
     * Get a value from the cache.
     *
     * @param $ip
     *
     * @return mixed
     */
    public function get($key)
    {
        if ($this->expireTime()) {
            return $this->cache->get($this->key($key));
        }
    }

    /**
     * Remove an ip address from cache.
     *
     * @param string $ip
     *
     * @return void
     */
    public function forget($key)
    {
        if ($this->expireTime()) {
            $this->cache->forget($this->key($key));
        }
    }

    /**
     * Erase the whole cache.
     *
     * @return void
     */
    public function flush()
    {
        $this->cache->flush();
    }

    /**
     * Store an item in the cache for a given number of minutes.
     *
     * @param string        $key
     * @param mixed         $value
     * @param int|null|bool $minutes
     *
     * @return void
     */
    public function put($key, $value, $minutes = null)
    {
        if ($timeout = $this->expireTime()) {
            $this->cache->put($this->key($key), $value, $minutes ?: $timeout);
        }
    }

    /**
     * Get cache expire time.
     *
     * @return int|bool
     */
    public function expireTime()
    {
        return $this->config->get('cache_expire_time');
    }
}
