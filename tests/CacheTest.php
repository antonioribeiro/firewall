<?php

namespace PragmaRX\Firewall\Tests;

class CacheTest extends TestCase
{
    public function setUp()
    {
        parent::setup();

        $this->cache = app('firewall.cache');
    }

    public function test_cache_put()
    {
        foreach (range(1, 100) as $counter) {
            $this->cache->put($key = '1234', $this->cache->get($key, 0) + 1, 10);
        }

        $this->assertEquals(100, $this->cache->get($key));
    }
}
