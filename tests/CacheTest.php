<?php

namespace PragmaRX\Firewall\Tests;

class CacheTest extends TestCase
{
    /**
     * @var \PragmaRX\Firewall\Firewall
     *                                  change one test
     */
    public $firewall;

    /**
     * @var \PragmaRX\Firewall\Repositories\Cache\Cache
     */
    public $cache;

    public function setUp(): void
    {
        parent::setup();

        $this->cache = app('firewall.cache');
    }

    public function testCacheHoldsCachedIp()
    {
        $this->firewall->blacklist($ip = '172.17.0.1');

        $this->firewall->find($ip);

        $this->assertTrue($this->cache->has($ip));
    }

    public function testCachePut()
    {
        $key = '1234';

        foreach (range(1, 100) as $counter) {
            $this->cache->put($key, $this->cache->get($key, 0) + 1, 10);
        }

        $this->assertEquals(100, $this->cache->get($key));
    }

    public function testDisabledCache()
    {
        $this->cache->put($key = '1234', $this->cache->get($key, 0) + 1, 10);
        $this->cache->put($key = '1234', $this->cache->get($key, 0) + 1, 10);

        $this->assertEquals(2, $this->cache->get($key));

        $this->assertTrue($this->cache->has($key));

        $this->config('cache_expire_time', false);

        $this->assertFalse($this->cache->has($key));

        $this->assertNull($this->cache->get($key));
    }

    public function testListCache()
    {
        $this->firewall->blacklist($ip = '172.17.0.1');

        $this->assertTrue($this->firewall->isBlacklisted($ip));

        $this->firewall->clear();

        $this->assertFalse($this->firewall->isBlacklisted($ip));

        $this->config('ip_list_cache_expire_time', 1);

        $this->firewall->blacklist($ip);

        $this->assertTrue($this->firewall->isBlacklisted($ip));

        $this->firewall->clear();

        $this->assertTrue($this->firewall->isBlacklisted($ip));
    }
}
