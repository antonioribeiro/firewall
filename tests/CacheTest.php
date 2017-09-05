<?php

namespace PragmaRX\Firewall\Tests;

class CacheTest extends TestCase
{
    public function setUp()
    {
        parent::setup();

        $this->cache = app('firewall.cache');

        $this->firewall = app('firewall');
    }

    public function test_cache_holds_cached_ip()
    {
        $this->firewall->blacklist($ip = '172.17.0.1');

        $this->firewall->find($ip);

        $this->assertTrue($this->cache->has($ip));
    }

    public function test_cache_put()
    {
        foreach (range(1, 100) as $counter) {
            $this->cache->put($key = '1234', $this->cache->get($key, 0) + 1, 10);
        }

        $this->assertEquals(100, $this->cache->get($key));
    }

    public function test_disabled_cache()
    {
        $this->cache->put($key = '1234', $this->cache->get($key, 0) + 1, 10);
        $this->cache->put($key = '1234', $this->cache->get($key, 0) + 1, 10);

        $this->assertEquals(2, $this->cache->get($key));

        $this->assertTrue($this->cache->has($key));

        $this->config('cache_expire_time', false);

        $this->assertFalse($this->cache->has($key));

        $this->assertNull($this->cache->get($key));
    }
}
