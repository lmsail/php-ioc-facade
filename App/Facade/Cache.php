<?php declare(strict_types=1);

namespace App\Facade;

/**
 * @see \App\Facade\Cache
 * @package App\Facade
 * @mixin \App\Facade\Cache
 * @method static string set($key, $value) 设置缓存
 */
class Cache extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）
     * @access protected
     * @return string
     */
    protected static function getFacadeClass()
    {
        return 'cache';
    }
}