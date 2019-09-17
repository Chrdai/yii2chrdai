<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 10:37
 */

namespace yii;

use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\UnknownClassException;
use yii\di\Container;

//定义是否开启调试模式的常量, 默认值为 false
defined('YII_DEBUG') or define('YII_DEBUG', false);

//定义是否启用错误处理的常量，默认值为 true
defined('YII_ENABLE_ERROR_HANDLER') or define('YII_ENABLE_ERROR_HANDLER', true);

//定义应用程序是否处于测试环境
defined('YII_ENV_TEST') or define('YII_ENV_TEST', YII_ENV === 'test');

//定义yii2框架的安装目录
defined('YII2_PATH') or define('YII2_PATH', __DIR__);

class BaseYii
{
    /**
     * Yii 自动加载机制使用的类映射。
     * 数组键是类名（没有前导反斜杠）， 数组值是相应的类文件路径（或 路径别名)。
     * 此属性主要影响 autoload() 的工作方式
     * @var array
     * @see autoload()
     */
    public static $classMap = [];

    /**
     * @var array 注册路径别名
     * @see getAlias()
     * @see setAlias()
     */
    public static $aliases = ['@yii' => __DIR__];

    /**
     * 应用程序实例
     * @var \yii\console\Application|\yii\web\Application the application instance
     */
    public static $app;

    /**
     * @var Container [[createObject()]] 使用的依赖注入（DI）容器。 您可以使用 \yii\di\Container::set() 来设置类及其初始属性值所需的依赖项。
     * @see createObject()
     * @see Container
     */
    public static $container;

    /**
     * @purpose: 类自动加载器
     * 1、在 $classMap 中搜索；类映射表的添加方式为：Yii::$classMap['foo\bar\MyClass'] = 'path/to/MyClass.php'; 或 Yii::$classMap['foo\bar\MyClass'] = '@path/to/MyClass.php';
     * 2、如果是带命名空间的类（例如 yii\base\Component）， 它将尝试包含与相应路径别名相关联的文件 （例如 @yii/base/Component.php）；
     * @param string $className 没有前导反斜杠“\”的完全限定类名
     * @throws UnknownClassException 如果类文件中不存在该类
     */
    public static function autoload($className)
    {
        if (isset(static::$classMap[$className])) {
            $classFile = static::$classMap[$className];
            //字符串的第一个字母
            if ($classFile[0] == '@') {
                $classFile = static::getAlias($classFile);
            }
        } elseif (strpos($className, '\\') !== false) {
            $classFile = static::getAlias('@' . str_replace('\\', '/', $className) . '.php', false);
            if ($classFile === false || !is_file($classFile)) {
                return;
            }
        } else {
            return;
        }

        include $classFile;

        if (YII_ENV && !class_exists($className, false) && !interface_exists($className, false) && !trait_exists($className, false)) {
            throw new UnknownClassException("Unable to find '$className' in file: $classFile. Namespace missing?");
        }
    }

    /**
     * 将路径别名转换为实际路径
     * 如果给定的别名不以 '@' 开头，则返回时不做更改；
     * 否则，查找与给定别名的开头部分匹配的最长注册别名。如果存在，请将给定别名的匹配部分替换为相应的注册路径。
     * 抛出异常或返回 false，具体取决于 $throwException 参数。
     * 例如，默认情况下，'@yii' 被注册为 Yii 框架目录的别名，例如 '/path/to/yii'。 别名 '@yii/web' 将被翻译成 '/path/to/yii/web'。
     * @param string $alias 要翻译的别名。
     * @param bool $trowException 如果给定的别名无效，是否抛出异常。 如果为 false 并且给出了无效的别名，则此方法将返回 false。
     * @return bool|mixed|string 与别名对应的路径，如果先前未注册根别名，则为 false。
     * @throws InvalidArgumentException 如果 $throwException 为 true 时别名无效。
     */
    public static function getAlias($alias, $trowException = true)
    {
        //如果给定的别名不以 '@' 开头，则返回时不做更改；
        if (strncmp($alias, '@', 1)) {
            return $alias;
        }

        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            }
        }

        if ($trowException) {
            throw new InvalidArgumentException("Invalid path alias: $alias");
        }

        return false;
    }

    /**
     * 注册一个路径的别名
     * @param string $alias 以“@”字符开头的别名。
     * @param string $path 别名所对应的路径
     */
    public static function setAlias($alias, $path)
    {
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if ($path !== null) {
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            if (!isset(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (is_string(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            } else {
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
        } elseif (isset(static::$aliases[$root])) {
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
    }

    /**
     * @purpose: 用 $properties 中的初始属性值来初始化对象
     * @param object $object 待初始化的对象
     * @param array $properties 键值对形式的属性数组
     * @return mixed
     */
    public static function configure($object, $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }

        return $object;
    }

    /**
     * 使用给定配置创建新对象。
     * 可以将此方法视为 new 运算符的增强版本。 该方法支持基于类名， 配置数组或匿名函数创建对象。
     * 以下是一些使用示例：
     *
     * ```php
     * // 使用类名创建对象
     * $object = Yii::createObject('yii\db\Connection');
     *
     * // 使用配置数组创建对象
     * $object = Yii::createObject([
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // c使用两个构造函数参数创建一个对象
     * $object = \Yii::createObject('MyClass', [$param1, $param2]);
     * ```
     *
     * 使用依赖注入容器，此方法还可以识别依赖对象， 实例化它们并将它们注入新创建的对象
     * @param string|array|callable $type 对象类型。可以使用以下形式之一指定：
     * 1、一个字符串：表示要创建的对象的类名
     * 2、配置数组：数组必须包含一个被视为对象类的 class 元素， 其余的键值对将用于初始化相应的对象属性
     * 3、PHP回调函数：要么是匿名函数，要么是表示类方法的数组（[$class 或 $object, $method]）。 callable 应返回正在创建的对象的新实例。
     * @param array $params 构造函数参数
     */
    public static function createObject($type, $params = [])
    {
        if (is_string($type)) {
            return static::$container->get($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return static::$container->get($class, $params, $type);
        } elseif (is_callable($type, true)) {
            return static::$container->invoke($type, $params);
        } elseif (is_array($type)) {
            throw new InvalidConfigException('Object configuration must be an array containing a "class" element.');
        }
        throw new InvalidConfigException('Unsupported configuration type: ' . gettype($type));
    }

}