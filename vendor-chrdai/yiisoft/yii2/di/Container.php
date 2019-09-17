<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/7
 * Time: 17:55
 */

namespace yii\di;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

class Container extends Component
{
    /**
     * @var array 按自身类型为key的单例对象数组。
     */
    private $_singletons = [];

    private $_definition = [];

    /**
     * @var array 缓存的依赖类对象，按类名/接口名索引
     */
    private $_reflections = [];

    /**
     * @var array 缓存的依赖项按类名/接口名索引, 每个类名都与构造函数参数类型或默认值列表关联。
     */
    private $_dependencies = [];

    /**
     * @var array 按对象类型索引的构造函数参数
     */
    private $_params = [];

    /**
     * 返回所请求类的实例。
     * 可以提供构造函数参数 ($params) 和对象配置 ($config)， 这些参数将在创建实例期间使用。
     * 如果类实现 yii\base\Configurable，则 $config 参数将作为最后一个参数 传递给构造函数；否则， 配置将在对象被实例化之后被应用。
     * 注意如果通过调用 setSingleton() 将类声明为单例， 则每次调用此方法时都将返回该类的相同实例。 在这种情况下， 只有在第一次实例化类时，才会使用构造函数参数和对象配置。
     * @param string $class 先前通过 set() 或 setSingleton() 注册的类名或别名（e.g. foo）。
     * @param array $params 构造函数参数值列表。 参数应该按照它们在构造函数声明中出现的顺序提供。 如果你想略过某些参数，你应该将剩下的索引用整数表示它们在构造函数参数列表中的位置。
     * @param array $config 将用于初始化对象属性的名键值对的列表。
     * @return object 请求类的实例。
     * @throws InvalidConfigException 如果类不能识别或对应于无效定抛出的异常
     */
    public function get($class, $params = [], $config = [])
    {
        if (isset($this->_singletons[$class])) {
            return $this->_singletons[$class];
        } elseif (!isset($this->_definition[$class])) {
            return $this->build($class, $params, $config);
        }

        $definition = $this->_definition[$class];
        if (is_callable($definition, true)) {
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            $object = call_user_func($definition, $this, $params, $config);
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);

            $config = array_merge($definition, $config);
            $params = $this->mergeParams($class, $params);

            if ($concrete === $class) {
                $object = $this->build($class, $params, $config);
            } else {
                $object = $this->get($class, $params, $config);
            }
        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }

        if (array_key_exists($class, $this->_singletons)) {
            //singleton
            return $this->_singletons[$class] = $object;
        }

        return $object;
    }

    /**
     * 将用户指定的构造函数的参数和通过 set() 注册的参数合并
     * @param string $class 类名，接口名或别名
     * @param array $params 构造函数的参数
     * @return array 合并后的参数
     */
    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        }

        $ps = $this->_params[$class];
        foreach ($params as $index => $value) {
            $ps[$index] = $value;
        }

        return $ps;
    }

    /**
     * 通过解析参数中的依赖项来调用回调。
     * 此方法允许调用回调并将类型提示的参数名称解析为 Container 的对象。 它还允许使用命名参数调用函数。
     * 例如，可以使用 Container 调用以下回调来解析格式化程序依赖项：
     *
     *  * ```php
     * $formatString = function($string, \yii\i18n\Formatter $formatter) {
     *    // ...
     * }
     * Yii::$container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     * 这将传递字符串 'Hello World!' 作为第一个参数， 以及由 DI 创建的格式化程序实例作为回调的第二个参数。
     * @param callable $callback 需要调用的回调。
     * @param array $params 函数的参数数组。 这可以是参数列表，也可以是表示命名函数参数的关联数组。
     * @return mixed 回调返回的值。
     */
    public function invoke(callable $callback, $params)
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
        }

        return call_user_func_array($callback, $params);
    }

    public function resolveCallableDependencies(callable $callback, $params)
    {
        if (is_array($callback)) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflection = new \ReflectionClass($callback);
        }

        $args = [];

        //判断$params是否为关联数组
        $associative = ArrayHelper::isAssociative($params);

        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            if ($class = $param->getName() !== null) {
                $className = $param->getName();
                if (version_compare(PHP_VERSION, '5.6.0', '>=') && $param->isVariadic()) {
                    array_merge($args, array_values($params));
                    break;
                } elseif ($associative && isset($params[$name]) && $params[$name] instanceof \Closure) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof \Closure) {
                    $args[] = array_shift($params);
                } elseif (isset(Yii::$app) && Yii::$app->has($name) && ($obj = Yii::$app->get($name)) instanceof \Closure) {
                    $args[] = $obj;
                } else {
                    try {
                        $args[] = $this->get($className);
                    } catch (NotInstantiableException $e) {
                        if ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }
                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }

        return $args;
    }

    /**
     * 创建指定类的实例。 此方法将解析指定类的依赖关系，实例化它们， 并且将它们注入到指定类的新实例中
     * @param string $class 类名
     * @param array $params 构造函数的参数
     * @param array $config 引用于新实例的配置
     * @return object 新创建的指定类的实例
     * @throws \yii\di\NotInstantiableException 从 2.0.9 版本开始，如果解析为抽象类或接口抛出的异常
     */
    protected function build($class, $params, $config)
    {
        /* @var $reflection \ReflectionClass  */
        list($reflection, $dependencies) = $this->getDependencies($class);

        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }

        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        //检查类是否可实例化

        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }

        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }

        $config = $this->resolveDependencies($config);

        if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // 将$config设置为最后一个参数 (如果已存在则覆盖)
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        }

        $object = $reflection->newInstanceArgs($dependencies);
        foreach ($config as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * 返回指定类的依赖项。
     * @param string $class 类名，接口名或别名
     * @return array 指定类的依赖关系。
     */
    protected function getDependencies($class)
    {
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        $reflection = new \ReflectionClass($class);
        //获取该类的构造函数
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $param) {
                //检查php版本是否大于5.6，参数是否是可变参数
                if (version_compare(PHP_VERSION , '5.6.0', '>=') && $param->isVariadic()) {
                    break;
                } elseif ($param->isDefaultValueAvailable()) {  //是有有默认值
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    $c = $param->getClass();
                    $dependencies[] = Instance::of($c === null ? null : $c->getName());
                }
            }
        }
        $this->_reflections[$class] = $reflection;
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * 通过将依赖项替换为实际对象实例来解析依赖关系
     * @param array $dependencies 依赖关系
     * @param \ReflectionClass $reflection 与依赖关联的类反射
     * @return array 已解决的依赖项
     * @throws \yii\base\InvalidConfigException 如果无法解决依赖关系或无法实现依赖关系抛出的异常。
     */
    public function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
            if ($dependency instanceof Instance) {
                if ($dependency->id !== null) {
                    $dependencies[$index] = $this->get($dependency->id);
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }

        return $dependencies;
    }

}