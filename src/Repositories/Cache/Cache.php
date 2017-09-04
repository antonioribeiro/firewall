<?php

namespace PragmaRX\Firewall\Repositories\Cache;

use Illuminate\Cache\CacheManager;
use PragmaRX\Firewall\Support\ServiceInstances;

class Cache
{
    use ServiceInstances;

    const CACHE_BASE_NAME = 'firewall.';

    private $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
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
     * @param $key
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
     * @param $key
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
     * @param $key
     *
     * @return mixed|null
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
     * @param $key
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
        return $this->config()->get('cache_expire_time');
    }
}
