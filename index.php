<?php
/**
 * 心得：IOC容器+Facade门面模式使得系统更加灵活，可高度自定义，灵活配置
 */

require __DIR__ . '/vendor/autoload.php';

use App\Container;
use App\Facade\Cache;

$provider = include __DIR__ . '/App/Provider.php';

# 将 Provider 中定义的类绑定到容器内
$container = Container::getInstance()->register($provider);

# 通过别名 cache 获取实例对象并调用 set 方法
echo $container->make('cache')->set('test', '123') . PHP_EOL;

# 也可直接传入类对象
echo $container->make(\App\Library\Cache\Redis::class)->set('test', '123') . PHP_EOL;

# 使用门面模式直接使用 Cache 实例对象调用 set 方法
echo Cache::set('test2', '456') . PHP_EOL;