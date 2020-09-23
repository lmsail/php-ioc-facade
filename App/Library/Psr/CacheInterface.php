<?php

<<<<<<< HEAD
namespace App\Library\Psr;
=======
namespace Psr\SimpleCache;
>>>>>>> 6b50caac589b392534ffe6bd72e4bb8bd88dfb11

interface CacheInterface
{
    public function get($key, $default = null);

    public function set($key, $value, $ttl = null);

    public function delete($key);

    public function clear();

    public function has($key);
}
