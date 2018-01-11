<?php

namespace SimpleLcache;

use Cache\IntegrationTests\SimpleCacheTest;
use LCache\Integrated;
use LCache\l1\L1CacheFactory;
use LCache\l2\Database;
use LCache\l2\L2;
//use LCache\l2\Redis;
use PDO;
use Psr\SimpleCache\CacheInterface;

//use Redis as PhpRedis;


class SimpleLcacheTest extends SimpleCacheTest
{
    const TTL = 10;
    protected $dbh;

    public function createSimpleCache()
    {
        /*
                $redis = new PhpRedis();
                $redis->connect('localhost');
                $l2 = new Redis($redis);
        */
        $this->dbh = new PDO('sqlite::memory:');
        $this->createSchema();
        $l2 = new Database($this->dbh);
        return self::create_lcache('Static', $l2, 'first_pool', self::TTL);
    }

    protected static function create_lcache(string $l1_driver, L2 $l2, string $pool, $ttl): CacheInterface
    {
        return new Cache(
            new Integrated((new L1CacheFactory())->create($l1_driver, $pool), $l2),
            'test',
            $ttl
        );
    }

    protected function createSchema($prefix = '')
    {
        $this->dbh->exec('PRAGMA foreign_keys = ON');

        $this->dbh->exec('CREATE TABLE ' . $prefix . 'lcache_events("event_id" INTEGER PRIMARY KEY AUTOINCREMENT, "pool" TEXT NOT NULL, "address" TEXT, "value" BLOB, "expiration" INTEGER, "created" INTEGER NOT NULL)');
        $this->dbh->exec('CREATE INDEX ' . $prefix . 'latest_entry ON ' . $prefix . 'lcache_events ("address", "event_id")');

        $this->dbh->exec('CREATE TABLE ' . $prefix . 'lcache_tags("tag" TEXT, "event_id" INTEGER, PRIMARY KEY ("tag", "event_id"), FOREIGN KEY("event_id") REFERENCES ' . $prefix . 'lcache_events("event_id") ON DELETE CASCADE)');
        $this->dbh->exec('CREATE INDEX ' . $prefix . 'rewritten_entry ON ' . $prefix . 'lcache_tags ("event_id")');
    }
}
