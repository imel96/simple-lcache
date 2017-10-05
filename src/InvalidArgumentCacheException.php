<?php

namespace SimpleLcache;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;

class InvalidArgumentCacheException extends InvalidArgumentException implements CacheInvalidArgumentException { }

