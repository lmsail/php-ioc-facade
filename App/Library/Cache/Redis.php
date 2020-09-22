<?php declare(strict_types=1);

namespace App\Library\Cache;

use Psr\SimpleCache\CacheInterface;

class Redis implements CacheInterface
{
    public function set($key, $value, $ttl = null) {
        return "RedisCache: key: {$key}, value: {$value}";
    }

    public function get($key, $default = null)
    {}

    public function delete($key)
    {}

    public function clear()
    {}

    public function has($key)
    {}
}