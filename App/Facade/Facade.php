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
<<<<<<< HEAD
    protected static function getFacadeClass()
    {}

    /**
     * 结合容器调用实际类的方法
     * @param $method
     * @param $params
     * @return mixed
     */
=======
    protected static function getFacadeClass() {}

    // 调用实际类的方法
>>>>>>> 6b50caac589b392534ffe6bd72e4bb8bd88dfb11
    public static function __callStatic($method, $params)
    {
        $class = Container::getInstance()->make(static::getFacadeClass());

        return call_user_func_array([$class, $method], $params);
    }
}