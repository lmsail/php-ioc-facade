<?php declare(strict_types=1);

namespace App\Library\Cache;

<<<<<<< HEAD
use App\Library\Psr\CacheInterface;
=======
use Psr\SimpleCache\CacheInterface;
>>>>>>> 6b50caac589b392534ffe6bd72e4bb8bd88dfb11

class File implements CacheInterface
{
    public function set($key, $value, $ttl = null) {
        return "FileCache: key: {$key}, value: {$value}";
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