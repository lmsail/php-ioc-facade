## 探索 PHP 设计模式之IOC容器+门面模式

---

### 整体架构（目录结构）

```txt
├── App
│   ├── Controller					# 控制器
│   │   ├── TestOne.php
│   │   └── TestTwo.php
│   ├── Facade							# 门面类
│   │   ├── Cache.php
│   │   └── Facade.php
│   ├── Library							# 类库
│   │   ├── Cache
│   │   └── Psr
│   ├── Container.php				# 容器类
│   └── Provider.php				# 定义载入容器文件
├── index.php								# 入口文件
```

### IOC 容器（Inversion of Control）控制反转

> 概念：遵循依赖倒置原则的一种代码设计方案，依赖的创建 (控制) 由主动变为被动 (反转)。
>
> 目的：代码解耦，提高代码的可维护性
>
> 下面是具体实现的代码，Controller 没什么好说的，就是简单创建的类，并定义 test 方法

**IOC容器类（Container.php）**

> “借（chao）鉴（xi）”的 Thinkphp6 IOC容器

```php
<?php declare(strict_types=1);

namespace App;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunctionAbstract;

class Container
{
    /**
     * 容器对象实例
     * @var static
     */
    protected static $instance;

    /**
     * 容器中的对象实例
     * @var array
     */
    protected $instances = [];

    /**
     * 容器绑定标识，别名 => 类实例对象
     * @var array
     */
    protected $bind = [];

    /**
     * 获取当前容器的实例（单例）
     * @access public
     * @return static
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new static;
        }
        return self::$instance;
    }

    /**
     * 绑定一个类、实例、接口实现到容器
     * @access public
     * @param string|array $abstract 类标识、接口
     * @param mixed        $concrete 要绑定的类、闭包或者实例
     */
    public function register($abstract, $concrete = null): Container
    {
        if ( is_array($abstract) ) {
            foreach ($abstract as $alias => $item) {
                $this->register($alias, $item);
            }
        } else {
            $abstract = $this->getAlias($abstract);
            $this->bind[$abstract] = $concrete;
        }
        return $this;
    }

    /**
     * 根据别名获取真实类名
     * @param  string $abstract
     * @return string
     */
    public function getAlias(string $abstract): string
    {
        if (isset($this->bind[$abstract])) {
            $bind = $this->bind[$abstract];
            if (is_string($bind)) {
                return $this->getAlias($bind);
            }
        }
        return $abstract;
    }

    /**
     * 获取容器内的类实例对象/创建类对象
     * @access public
     * @param string $abstract 者标识
     * @param array $vars 变量
     * @throws
     */
    public function make(string $abstract, array $vars = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $object = $this->invokeClass($abstract, $vars);
        $this->instances[$abstract] = $object;
        return $object;
    }

    /**
     * 调用反射执行类的实例化 支持依赖注入
     * @access public
     * @param string $class 类名
     * @param array $vars 参数
     * @return mixed
     * @throws \Exception
     */
    public function invokeClass(string $class, array $vars = [])
    {
        try {
            $reflect = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw new \Exception('class not exists: ' . $class);
        }
        $constructor = $reflect->getConstructor();
        $args = $constructor ? $this->bindParams($constructor, $vars) : [];
        return $reflect->newInstanceArgs($args);
    }

    /**
     * 绑定参数
     * @access protected
     * @param ReflectionFunctionAbstract $reflect 反射类
     * @param array                      $vars    参数
     * @return array
     */
    protected function bindParams(ReflectionFunctionAbstract $reflect, array $vars = []): array
    {
        if ($reflect->getNumberOfParameters() == 0) {
            return [];
        }
        // 判断数组类型 数字数组时按顺序绑定参数
        reset($vars);
        $type   = key($vars) === 0 ? 1 : 0;
        $params = $reflect->getParameters();
        $args   = [];
        foreach ($params as $param) {
            $class = $param->getClass();
            if ($class) {
                $args[] = $this->getObjectParam($class->getName(), $vars);
            } elseif (1 == $type && !empty($vars)) {
                $args[] = array_shift($vars);
            } else {
                throw new InvalidArgumentException('method param miss:' . $param->getName());
            }
        }
        return $args;
    }

    /**
     * 获取对象类型的参数值
     * @access protected
     * @param string $className 类名
     * @param array  $vars      参数
     * @return mixed
     */
    protected function getObjectParam(string $className, array &$vars)
    {
        $array = $vars;
        $value = array_shift($array);
        if ($value instanceof $className) {
            $result = $value;
            array_shift($vars);
        } else {
            $result = $this->make($className);
        }
        return $result;
    }
}
```

**容器Provider定义文件（Provider.php）**

```php
<?php
/**
 * 容器Provider定义文件
 * 别名 => 类实例对象
 */

use App\Controller\TestOne;
use App\Library\Cache\Redis;

return [
    'testOne' => TestOne::class,
    'cache'   => Redis::class, # 这里可灵活配置cache的指向
];
```

**入口文件（index.php）**

```php
<?php
/**
 * 心得：结合IOC容器+Facade门面模式使得系统更加灵活，可高度自定义，灵活配置
 */
require __DIR__ . '/vendor/autoload.php';

use App\Container;
use App\Facade\Cache;

$provider = include __DIR__ . '/App/Provider.php';

# 将 Provider 中定义的类绑定到容器内
$container = Container::getInstance()->register($provider);

# $container = $container->make('testOne')->test();

# 通过别名 cache 获取实例对象并调用 set 方法
echo $container->make('cache')->set('test', '123') . PHP_EOL;

# 使用门面模式直接使用 Cache 实例对象调用 set 方法
echo Cache::set('test2', '456') . PHP_EOL;
```

---

### 依赖注入（DI）

> 概念：通过参数的方式从外部传入依赖，将依赖的创建由主动变为被动 (实现了控制反转)。

**雏形**

```php
<?php
class A {
    public $count = 10;
}
class B {
    public $_object;
    public function __construct(A $a)
    {
        $this->_object = $a;
    }
    public function testTwo() {
        return $this->_object->count;
    }
}
$b = new B((new A()));
echo $b->testTwo();
```

**有了IOC容器，我们可以实现自动注入依赖**

```php
<?php
class A {
    public $count = 10;
}
class B {
    public $_object;
    public function __construct(A $a)
    {
        $this->_object = $a;
    }
    public function testTwo() {
        return $this->_object->count;
    }
}
# 注册容器
$container = Container::getInstance()->register('a', A::class);
# 得到 A 实例对象并调用 testTwo 方法
echo $container->make('a')->testTwo();
```

---

### 外观模式（Facade）

> 概念：外部与一个子系统的通信必须通过一个统一的外观对象进行，为子系统中的一组接口提供一个一致的界面，外观模式定义了一个高层接口，这个接口使得这一子系统更加容易使用！
>
> 目的：使得系统代码更加灵活，松耦合，易移植

**门面基类（Facade.php）**

```php
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
      	# 结合容器，获取实例对象
        $class = Container::getInstance()->make(static::getFacadeClass());
        return call_user_func_array([$class, $method], $params);
    }
}
```

**实现一个缓存案例**

> 1、使用 cache 别名，将 Cache 类注册进容器内；
>
> 2、Library 下新建 Cache\Redis 与 Cache\File 两个类。后面可以通过 Provider 进行切换
>
> 3、Library 下新建 Psr\CacheInterface 接口，约束 Cache\Redis 与 Cache\File

```php
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
```

```php
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
```

```php
<?php declare(strict_types=1);

namespace App\Library\Cache;

use Psr\SimpleCache\CacheInterface;

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
```

```php
<?php

namespace Psr\SimpleCache;

interface CacheInterface
{
    public function get($key, $default = null);

    public function set($key, $value, $ttl = null);

    public function delete($key);

    public function clear();

    public function has($key);
}

```

---

### 参考与借鉴

- [简书 - 搞懂依赖注入, 用 PHP 手写简易 IOC 容器](https://www.jianshu.com/p/844ae48975f4)
- [Thinkphp6.0 - 优秀的 PHP 框架](https://github.com/top-think/framework)
- [Laravel5.x - 优雅的 PHP 框架](https://github.com/laravel/framework)

### 接下来（加油...）

> 可以自己动手，结合IOC容器 + 依赖注入 + 外观模式实现自己的小框架，更加深入的了解现代 php 编码的设计模式！！

