<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/9
 * Time: 15:28
 */

namespace yii\base;
use yii\helpers\StringHelper;

/**
 * Event 是所有事件类的基类。
 * 它封装了与事件关联的参数。 $sender 属性描述了谁触发了该事件。 并且 $handled 属性指示是否处理了事件。
 * 如果事件处理程序将 $handled 设置为 true， 其余的未经处理的处理程序将不再被调用来处理该事件。
 * 此外，在附加事件处理程序时，可以传递额外的数据， 并在调用事件处理程序时通过 $data 属性使用
 * Class Event
 * @package yii\base
 */
class Event extends BaseObject
{

    /**
     * @var string 事件名称，[[Component::trigger()]] and [[trigger()]].会用到该属性。
     */
    public $name;
    /**
     * @var object 事件触发者。
     * 如果没有设置，该属性将设置为调用其' trigger() '方法的对象。
     * 当该事件是在静态上下文中触发的类级事件时，此属性也可能是“null”。
     */
    public $sender;

    /**
     * @var bool 事件是否已经处理，默认为 false.
     * 如果事件处理程序将 $handled 设置为 true， 其余的未经处理的处理程序将不再被调用来处理该事件。
     */
    public $handled = false;

    /**
     * @var mixed 附加事件处理程序时传递给[[Component::on()]]的数据。
     * 注: 这取决于当前正在执行的事件处理程序
     */
    public $data;

    /**
     * @var array 包含所有全局注册的事件处理程序
     */
    private static $_event = [];

    /**
     * @var array 为通配符模式附加的全局注册事件处理程序(事件名称通配符=>处理程序)
     */
    private static $_eventWildcards = [];

    /**
     * 触发类级事件。
     * 此方法将调用附加到指定类及其所有父类的命名事件的事件处理程序。
     * @param string|object $class 指定类级事件的对象或完全限定的类名。
     * @param string $name 事件名称
     * @param null $event 事件参数。如果没有设置，将创建一个默认的[[Event]]对象。
     */
    public static function trigger($class, $name, $event = null)
    {
        $wildcardEventHandlers = [];
        foreach (self::$_eventWildcards as $nameWildcard => $classHandlers) {
            if (!StringHelper::matchWildcard($nameWildcard, $name)) {
                continue;
            }
            $wildcardEventHandlers = array_merge($wildcardEventHandlers, $classHandlers);
        }

        if (empty(self::$_event[$name]) && empty($wildcardEventHandlers)) {
            return;
        }

        if ($event === null) {
            $event = new static();
        }
        $event->handled = false;
        $event->name = $name;

        if (is_object($class)) {
            if ($event->sender === null) {
                $event->sender = $class;
            }
            $class = get_class($class);
        } else {
            $class = ltrim($class, '\\');
        }

        $classes = array_merge(
            [$class],
            class_parents($class, true),
            class_implements($class, true)
        );

        foreach($classes as $class) {
            $eventHandlers = [];
            foreach ($wildcardEventHandlers as $classWildcard => $handlers) {
                if (!StringHelper::matchWildcard($classWildcard, $class)) {
                    $eventHandlers = array_merge($eventHandlers, $handlers);
                    unset($wildcardEventHandlers[$classWildcard]);
                }
            }

            if (!empty(self::$_event[$name][$class])) {
                $eventHandlers = array_merge($eventHandlers, self::$_event[$name][$class]);
            }

            foreach ($eventHandlers as $handler) {
                $event->data = $handler[1];
                call_user_func($handler[0], $event);
                if ($event->handled) {
                    return;
                }
            }
        }
    }
}