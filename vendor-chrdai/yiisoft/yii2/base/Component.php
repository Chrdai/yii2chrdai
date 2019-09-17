<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 17:27
 */

namespace yii\base;

use Yii;
use yii\helpers\StringHelper;

/**
 * @purpose: 组件类用于实现属性、事件和行为
 *
 * Event : 事件是一种将自定义代码注入到已存在的中，进而对类进行拓展
 * 事件名称的 name 需要是一个唯一值，而且大小写敏感
 *
 * 可以将一个或多个PHP回调函数（事件句柄）附加到一个事件上。调用trigger()来触发事件。当触发事件时，事件处理程序将按其添加的顺序自动调用。
 *
 * 将事件句柄附加到事件 ，使用 on() 方法如下：
 *
 * ```php
 * $post->on('update', function ($event) {
 *     // send email notification
 * });
 * ```
 * 当然附件的事件可以是：
 * 1、匿名函数 ： `function ($event) { ... }`
 * 2、对象方法 ： `[$object, 'handleAdd']`
 * 3、静态方法 ： `['Page', 'handleAdd']`
 * 4、全局方法 ： `'handleAdd'`
 *
 * 上面的方法需要像这样的格式 ：
 *
 * ```php
 * function foo($event)
 * ```
 *
 * 其中 $event 是一个 yii\base\Event 对象，其中包含与该事件相关的参数
 *
 * 当使用配置数组的方式配置组件时，还可以将处理程序附加到事件。语法如下所示
 *
 * ```php
 * [
 *     'on add' => function ($event) { ... }
 * ]
 * ```
 *
 * 其中“on add”表示将事件附加到“add”事件。
 *
 * 如果希望将添加到事件的额外数据与事件处理程序关联，然后在调用处理程序时访问它。可以这样：
 *
 * ```php
 * $post->on('update', function ($event) {
 *     // the data can be accessed via $event->data
 * }, $data);
 * ```
 *
 * 行为是yii\base\behavior或其子类的实例。组件可以附加一个或多个行为。当行为附加到组件时，可以通过组件直接访问其公共属性和方法，就好像组件拥有这些属性和方法一样。
 *
 * 要将行为附加到组件，请在behavior()中声明它，或显式地调用attachBehavior()。在behavior()中声明的行为会自动附加到相应的组件
 *
 * 在使用配置数组配置组件时，还可以将行为附加到组件。语法如下所示 :
 *
 * ```php
 * [
 *     'as tree' => [
 *         'class' => 'Tree',
 *     ],
 * ]
 * ```
 *
 * 其中 as tree 表示附加一个名为 tree 的行为，数组将传递给Yii::createObject()来创建行为对象
 *
 *
 * Class Component
 * @package yii\base
 */
class Component extends BaseObject
{
    /**
     * @var array 附加的事件处理程序(事件名称=>处理程序)
     */
    private $_events = [];

    /**
     * @var Behavior[]|null 附加的行为(behavior name => behavior) , 当没有初始化的时候，该值为null
     */
    private $_behaviors;

    /**
     * @var array 为通配符模式附加的事件处理程序(事件名称通配符=>处理程序)
     */
    private $_eventWildcards = [];

    /**
     * 返回组件属性的值
     *
     * 此方法将按以下顺序检查并相应地执行：
     * 1、是否有定义 getter 方法
     * 2、是否有定义属性。
     *
     * @param string $name 属性名
     * @return mixed 属性值或行为属性的值
     * @throws UnknownPropertyException 如果属性未定义
     * @throws InvalidCallException 如果属性为只读属性
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            // read property, e.g. getName()
            return $this->$getter();
        }

        // behavior property
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canGetProperty($name)) {
                return $behavior->$name;
            }
        }

        if (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Getting write-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Getting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * 设置组件属性的值。
     *
     * 此方法将按以下顺序检查并相应地执行:
     * 1、类中存在与指定的名称关联的`setter`方法
     * 2、“on xyz”格式的事件:将处理程序附加到事件“xyz”
     * 3、“as xyz”格式的行为:附加名为“xyz”的行为
     * 4、行为的属性:设置行为属性值
     *
     * @param string $name 属性名或事件名
     * @param mixed $value 属性值
     * @throws UnknownPropertyException 如果属性未定义
     * @throws InvalidCallException 如果属性为只读属性
     * @see __get()
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            //set property
            $this->$setter($value);

            return;
        } elseif (strncmp($name, 'on', 3) ===0) {
            $this->on(trim(substr($name, 3)), $value);

            return;
        } elseif (strncmp($name, 'as', 3) ===0 ){
            // as behavior: attach behavior
            $name = trim(substr($name, 3));
            $this->attachBehavior($name, $value instanceof Behavior ? $value : Yii::createObject($value));

            return;
        }

        // behavior property
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canSetProperty($name)) {
                $behavior->$name = $value;
                return;
            }
        }

        if (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Setting read-only property: ' . get_class($this) . '::' . $name);
        }

        throw new UnknownPropertyException('Setting unknown property: ' . get_class($this) . '::' . $name);
    }

    /**
     * 检查属性是否已设置，即是否已定义且不为空。
     *
     * 此方法将按以下顺序检查并相应地执行:
     * 1、由setter定义的属性:返回是否设置了该属性
     * 2、行为的属性:返回是否设置了该属性
     * 3、对于不存在的属性返回“false”
     *
     * @param string $name 属性名或事件名
     * @return bool 属性名是否有设置
     * @see http://php.net/manual/en/function.isset.php
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }

        // behavior property
        $this->ensureBehaviors();
        foreach ($this->_behaviors as $behavior) {
            if ($behavior->canGetProperty($name)) {
                return $behavior->$name !== null;
            }
        }

        return false;
    }

    /**
     * 返回一个值，该值指示是否可以设置属性。
     *
     * ```
     * 满足如下情况，则为可写属性：
     * 1、类中存在与指定的名称关联的`setter`方法
     * 2、类中原来就存在的成员变量 (当$checkVars = true时)
     * 3、附加行为具有给定名称的可写属性(当“$ checkbehavior”为 true 时)。
     * ```
     *
     * @param string $name 属性名
     * @param bool $checkVars 是否将成员变量当做属性
     * @param bool $checkBehaviors 是否将行为的属性视为此组件的属性
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true, $checkBehaviors = true)
    {
        if (method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name)) {
            return true;
        } elseif ($checkBehaviors) {
            $this->ensureBehaviors();
            foreach ($this->_behaviors as $behavior) {
                if ($behavior->canSetProperty($name, $checkVars)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 将事件处理程序附加到事件.
     * 事件处理程序必须是一个有效的PHP回调函数。下面是一些例子:
     *
     * ```
     * function ($event) { ... }         // 匿名函数
     * [$object, 'handleClick']          // 对象方法 $object->handleClick()
     * ['Page', 'handleClick']           // 静态方法 Page::handleClick()
     * 'handleClick'                     // 全局方法 global function handleClick()
     * ```
     * 必须使用以下方式定义事件处理程序：
     *
     * ```
     * function ($event)
     * ```
     * 其中' $event '是一个[[event]]对象，其中包含与该事件关联的参数。
     *
     * ```php
     * $component->on('event.group.*', function ($event) {
     *     Yii::trace($event->name . ' is triggered.');
     * });
     *
     * @param string $name 事件名称
     * @param callable $handler 事件处理函数
     * @param mixed $data 事件触发时要传递给事件处理程序的数据。
     * 当调用事件处理程序时，可以通过[[event::data]]访问该数据。
     * @param bool $append 是否将新的事件处理程序追加到现有处理程序列表的末尾。如果为false，新的处理程序将插入到现有处理程序列表的开头。
     */
    public function on($name, $handler, $data = null, $append = true)
    {
        $this->ensureBehaviors();

        if (strpos($name, '*') !== false) {
            if ($append || empty($this->_eventWildcards[$name])) {
                $this->_eventWildcards[$name][] = [$handler, $data];
            } else {
                array_unshift($this->_eventWildcards[$name], [$handler, $data]);
            }
            return;
        }

        if ($append || empty($this->_events[$name])) {
            $this->_events[$name][] = [$handler, $data];
        } else {
            array_unshift($this->_events[$name], [$handler, $name]);
        }
    }

    /**
     * 从该组件中分离现有事件处理程序。
     * 该方法和 on() 方法是相反功能。
     * 如果传递通配符模式作为事件名称，则只删除使用此通配符注册的处理程序，
     * 而使用与此通配符匹配的普通名称注册的处理程序将保留。
     * @param string $name 事件名称
     * @param callable $handler 待被移除的事件处理函数，如果为null，则该名称所有相关的事件处理函数都会被移除。
     * @return bool 如果事件函数存在且成功被移除返回true，否则返回false。
     */
    public function off($name, $handler = null)
    {
        $this->ensureBehaviors();
        if (empty($this->_events[$name]) && empty($this->_eventWildcards[$name])) {
            return false;
        }

        if ($handler === null) {
            unset($this->_events[$name], $this->_eventWildcards[$name]);
            return true;
        }

        $removed = false;

        //普通的事件
        if (isset($this->_events[$name])) {
            foreach ($this->_events[$name] as $i => $event) {
                if ($event[0] === $handler) {
                    unset($this->_events[$name][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $this->_events[$name] = array_values($this->_events[$name]);
                return $removed;
            }
        }

        //通配符事件
        if (isset($this->_eventWildcards[$name])) {
            foreach ($this->_eventWildcards[$name] as $i => $event) {
                if ($event[0] === $handler) {
                    unset($this->_eventWildcards[$name][$i]);
                    $removed = true;
                }
            }
            if ($removed) {
                $this->_eventWildcards[$name] = array_values($this->_eventWildcards[$name]);
                //删除空通配符以保存将来冗余的正则表达式检查
                if (empty($this->_eventWildcards[$name])) {
                    unset($this->_eventWildcards[$name]);
                }
            }
        }

        return $removed;
    }

    /**
     * 触发一个事件.
     * 此方法表示当一个事件发生时。它调用事件的所有附加处理程序，包括类级别的处理程序。
     * @param string $name 事假拿名次
     * @param Event|null $event 事件参数。如果没有设置，将创建一个默认的[[Event]]对象。
     */
    public function trigger($name, Event $event = null)
    {
        $this->ensureBehaviors();

        $eventHandlers = [];
        foreach ($this->_eventWildcards as $wildcard => $handlers) {
            if (StringHelper::matchWildcard($wildcard, $name)) {
                $eventHandlers = array_merge($eventHandlers, $handlers);
            }
        }

        if (!empty($this->_events[$name])) {
            $eventHandlers = array_merge($eventHandlers, $this->_events[$name]);
        }

        if (!empty($eventHandlers)) {
            if ($event === null) {
                $event = new Event();
            }
            if ($event->sender === null) {
                $event->sender = $this;
            }
            $event->handled = false;
            $event->name = $name;
            foreach ($eventHandlers as $handler) {
                $event->data = $handler[1];
                call_user_func($handler[0], $event);
                //如果事件已经处理就停下来
                if ($event->handled) {
                    return;
                }
            }
        }

        Event::trigger($this, $name, $event);
    }

    /**
     * 确保[[behavior()]]中声明的行为附加到此组件。
     */
    public function ensureBehaviors()
    {
        if ($this->_behaviors === null) {
            $this->_behaviors = [];
            foreach ($this->behaviors() as $name => $behavior) {
                $this->attachBehaviorInternal($name, $behavior);
            }
        }
    }

    /**
     * 将行为附加到此组件。
     * 此方法将基于给定的配置创建行为对象。然后通过调用[[behavior::attach()]方法，将行为对象附加到这个组件。
     * @param string $name 行为名称
     * @param string|array|Behavior $behavior 行为配置，可以是以下情况之一：
     *
     * - 一个[[Behavior]] 对象
     * - 指定行为类的字符串
     * - 一个对象配置数组，它将被传递给[[Yii::createObject()]]来创建行为对象。
     *
     * @return Behavior 行为对象
     * @see detachBehavior()
     */
    public function attachBehavior($name, $behavior)
    {
        $this->ensureBehaviors();
        return $this->attachBehaviorInternal($name, $behavior);
    }

    /**
     * 将行为附加到此组件
     * @param string|int $name 行为的名称。如果这是一个整数，这意味着行为是一个匿名的行为。
     * 否则该行为是一个已命名的行为，任何具有相同名称的现有行为都将先被detach()后重新attach()。
     * @param string|array|Behavior $behavior 即将被附加的行为
     * @return mixed Behavior 附加的行为。
     */
    private function attachBehaviorInternal($name, $behavior)
    {
        if (!($behavior instanceof Behavior)) {
            $behavior = Yii::createObject($behavior);
        }
        if (is_int($name)) {
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        } else {
            if (isset($this->_behaviors[$name])) {
                $this->_behaviors[$name]->detach();
            }
            $behavior->attach($this);
            $this->_behaviors[] = $behavior;
        }

        return $behavior;
    }

    /**
     * @return array 返回此组件应具有的行为列表。
     * 子类可以覆盖此方法来指定它们想要作为的行为.
     * 方法的返回值应该是由行为名称索引的行为对象或配置数组。行为配置可以是指定行为类的字符串，也可以是以下结构的数组.
     *
     * ```php
     * 'behaviorName' => [
     *     'class' => 'BehaviorClass',
     *     'property1' => 'value1',
     *     'property2' => 'value2',
     * ]
     * ```
     *
     * 注意，行为类必须从yii\base\behavior扩展。可以使用名称或匿名方式附加行为。当使用名称作为数组键时，使用这个名称，
     * 稍后可以使用getBehavior()检索行为，或者使用detachBehavior()分离行为。无法检索或分离匿名行为。
     *
     * 此方法中声明的行为将自动(按需)附加到组件
     */
    public function behaviors()
    {
        return [];
    }
}