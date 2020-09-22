<?php declare(strict_types=1);

namespace App\Facade;

use App\Container;

class Facade
{
    /**
     * 获取当前Facade对应类名,由子类实现
     * @access protected
     * @return string
     */
    protected static function getFacadeClass() {}

    // 调用实际类的方法
    public static function __callStatic($method, $params)
    {
        $class = Container::getInstance()->make(static::getFacadeClass());

        return call_user_func_array([$class, $method], $params);
    }
}