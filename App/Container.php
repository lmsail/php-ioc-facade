<?php declare(strict_types=1);

namespace App;

use Closure;
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
        } elseif( $concrete instanceof Closure ) {
            $this->bind[$abstract] = $concrete;
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
     * @param array  $vars  参数
     * @return mixed
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