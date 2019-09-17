<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 18:30
 */

namespace yii\di;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * 要使用 ServiceLocator， 首先需要通过调用 set() 或 setComponents() 向定位器注册具有相应组件定义的组件 IDs。
 * 然后你可以通过调用 get() 去检索具有指定 ID 的组件。 定位器将根据定义自动实例化和配置组件。
 *
 * 例如：
 *
 * ```php
 * $locator = new \yii\di\ServiceLocator;
 * $locator->setComponents([
 *     'db' => [
 *         'class' => 'yii\db\Connection',
 *         'dsn' => 'sqlite:path/to/file.db',
 *     ],
 *     'cache' => [
 *         'class' => 'yii\caching\DbCache',
 *         'db' => 'db',
 *     ],
 * ]);
 *
 * $db = $locator->get('db');  // or $locator->db
 * $cache = $locator->get('cache');  // or $locator->cache
 * ```
 * 因为 yii\base\Module 从 ServiceLocator 继承，所以模型和应用程序都是服务定位器。 模块添加 tree traversal 用于服务解析。
 *
 * Class ServiceLocator
 * @package yii\di
 */
class ServiceLocator extends Component
{
    /**
     * @var array 以 id 为 key 的组件实例对象。
     */
    private $_components = [];

    /**
     * @var array 保存注册的组件
     */
    private $_definitions = [];

    /**
     * {@inheritdoc}
     * 返回具有指定 ID 的组件实例。
     * @param string $id 组件 ID（e.g. db）。
     * @param bool $throwException 如果 $id 之前未在定位器中注册，是否抛出一个异常。
     * @return mixed|null 指定 ID 的组件。如果 $throwException 为 false 并且 $id 在之前没有被注册，将会返回 null。
     * @throws InvalidConfigException 如果 $id 为不存在的组件ID, 抛出的异常。
     */
    public function get($id, $throwException = true)
    {
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }

        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            if (is_object($definition) && !$definition instanceof \Closure) {
                return $this->_components[$id] = $definition;
            }

            return $this->_components[$id] = Yii::createObject($definition);
        } elseif ($throwException) {
            throw new InvalidConfigException("Unknown component ID: $id");
        }

        return null;
    }

    /**
     * 用定位器注册一个组件。
     *
     * 例如：
     *
     * ```php
     * // 1、类名：
     * $locator->set('cache', 'yii\caching\FileCache');
     *
     * // 2、配置数组
     * $locator->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // 3、匿名函数
     * $locator->set('cache', function ($params) {
     *     return new \yii\caching\FileCache;
     * });
     *
     * // 4、实例
     * $locator->set('cache', new \yii\caching\FileCache);
     * ```
     * 如果具有相同 ID 的组件定义已经存在，则将覆盖它。
     *
     * @param string $id 组件 ID（e.g. db）
     * @param mixed $definition 使用定位器注册的组件。 它可以是以下之一：
     * 1、类名。
     * 2、配置数组：数组包含键值对，当调用 get() 时， 将用于初始化新创建的对象的属性值。 class 是必须的，代表要创建的对象的类。
     * 3、PHP 回调：匿名函数或表示类方法的数组（e.g. ['Foo', 'bar']）。 回调将会由 get() 调用，以返回与指定组件ID关联的对象。
     * 4、对象：当调用 get() 时，将会返回对象。
     */
    public function set($id, $definition)
    {
        unset($this->_components[$id]);

        if ($definition === null) {
            unset($this->_definitions[$id]);
            return;
        }

        if (is_object($definition) || is_callable($definition, true)) {
            //对象，类名和回调函数
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new InvalidConfigException("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    /**
     * {@inheritdoc}
     *
     * 返回一个值，该值表示定位器是否具有指定的组件定义或是否已实例化该组件。 此方法根据 $checkInstance 的值返回不同的结果。
     * 如果 $checkInstance 为 false（default）， 此方法将返回一个表示定位器是否具有指定组件定义的值。
     * 如果 $checkInstance 为 true， 此方法会返回一个表示定位器是否已经实例化指定组件的值。
     * @param string $id 组件 ID（e.g. db）。
     * @param bool $checkInstance 是否应检查组件是否已共享和实例化。
     * @return bool 是否定位器具有指定的组件定义或已实例化组件。
     */
    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    /**
     * 在定位器中注册一组组件.
     * 这是 set() 的批量版本。 参数应该是一个数组，其键是组件 IDs，并且值是相应的组件定义。
     * 有关如何指定组件 IDs 和定义的更多详细信息，请参阅 set()。
     * 如果具有相同 ID 的组件定义已经存在，则将覆盖它。
     * 以下是注册两个组件定义的示例：
     *
     * ```php
     * [
     *     'db' => [
     *         'class' => 'yii\db\Connection',
     *         'dsn' => 'sqlite:path/to/file.db',
     *     ],
     *     'cache' => [
     *         'class' => 'yii\caching\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     * @param array $components 组件或实例
     */
    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }
}