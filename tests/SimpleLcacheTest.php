<?php

namespace SimpleLcache;

use Cache\IntegrationTests\SimpleCacheTest;
use Redis as PhpRedis;
use LCache\Integrated;
use LCache\l1\L1CacheFactory;
use LCache\l2\L2;
use LCache\l2\Redis;

class SimpleLcacheTest extends SimpleCacheTest {
	const TTL = 10;

	public function createSimpleCache() {
		$redis = new PhpRedis();
		$redis->connect('localhost');
		$l2 = new Redis($redis);
		return self::create_lcache('APCu', $l2, 'first_pool', self::TTL);
	}

	protected static function create_lcache(string $l1_driver, L2 $l2, string $pool, $ttl): SimpleCache {
		return new SimpleCache(
			new Integrated((new L1CacheFactory())->create($l1_driver, $pool), $l2),
			'test',
			$ttl
		);
	}
}
