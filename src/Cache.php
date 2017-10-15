<?php

namespace SimpleLcache;

use DateInterval;
use Traversable;
use LCache\Address;
use LCache\Integrated;
use Psr\SimpleCache\CacheInterface;

/**
 * This class implements CacheInterface, but does not give independent
 * logical datastore between instances as required by the PSR-16
 */
class Cache implements CacheInterface
{
    const ILLEGAL_CHAR_LIST = '{}()\/@:';

    protected $bin;
    protected $lcache;
    protected $synchronised;

    public function __construct(Integrated $lcache, string $bin)
    {
        $this->lcache = $lcache;
        $this->bin = $bin;
        $this->synchronised = false;
        $this->synchronise();
    }

    public function get($key, $default = null)
    {
        self::validate_key($key);
        $this->synchronise();

        $value = $this->lcache->get(new Address($this->bin, $key));
        return is_null($value) ? $default : $value;
    }

    public function getMultiple($keys, $default = null)
    {
        self::validate_args($keys);
        $result = [];

        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    public function set($key, $value, $ttl = null)
    {
        self::validate_key($key);
        if (!is_int($ttl) && !is_null($ttl) && !$ttl instanceof DateInterval) {
            $ttl = is_string($ttl) ? $ttl : serialize($ttl);
            throw new InvalidArgumentCacheException("'$ttl' is not a legal ttl");
        }
        if ($ttl instanceof DateInterval) {
            $ttl = $ttl->s;
        } elseif ($ttl < 0) {
            return false;
        }
        $this->lcache->set(new Address($this->bin, $key), $value, $ttl);
        return true;
    }

    public function setMultiple($values, $ttl = null)
    {
        self::validate_args($values);

        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    public function delete($key)
    {
        self::validate_key($key);
        $this->lcache->delete(new Address($this->bin, $key));
        return true;
    }

    public function deleteMultiple($keys)
    {
        self::validate_args($keys);

        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    public function clear()
    {
        $this->lcache->delete(new Address($this->bin));
        return true;
    }

    public function has($key)
    {
        self::validate_key($key);
        return $this->lcache->exists(new Address($this->bin, $key));
    }

    protected function synchronise()
    {
        if (!$this->synchronised) {
            $this->synchronised = is_null($this->lcache->synchronize()) ? false : true;
        }
    }

    protected static function validate_key($key)
    {
        if (!is_string($key) || strval($key) === '' || strpbrk($key, self::ILLEGAL_CHAR_LIST) !== false) {
            $key = is_string($key) ? $key : serialize($key);
            throw new InvalidArgumentCacheException("'$key' is not a legal key");
        }
    }

    protected static function validate_args($args)
    {
        if (!is_array($args) && !($args instanceof Traversable)) {
            throw new InvalidArgumentCacheException("'$args' is neither an array nor a Traversable");
        }
    }
}
