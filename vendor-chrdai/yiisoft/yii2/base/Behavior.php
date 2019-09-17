<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/9
 * Time: 11:41
 */

namespace yii\base;


class Behavior extends BaseObject
{
    /**
     * @var Component|null 将要附加到的组件。
     */
    public $owner;

    /**
     * 为[[owner]]的事件声明事件处理程序.
     * 子类可以覆盖此方法来声明应该将哪些PHP回调附加到$owner组件的事件.
     * 当行为附加到$owner时，回调将附加到$owner的事件;当行为与组件分离时，它们将与事件分离。
     * 回调可以是以下情况：
     *
     * - method in this behavior: `'handleClick'`, equivalent to `[$this, 'handleClick']`
     * - object method: `[$object, 'handleClick']`
     * - static method: `['Page', 'handleClick']`
     * - anonymous function: `function ($event) { ... }`
     *
     * 示例：
     *
     * ```php
     * [
     *     Model::EVENT_BEFORE_VALIDATE => 'myBeforeValidate',
     *     Model::EVENT_AFTER_VALIDATE => 'myAfterValidate',
     * ]
     * ```
     * @return array 事件(array keys)和相应的事件处理程序方法(array values)。
     */
    public function events()
    {
        return [];
    }

    /**
     * 将行为对象attaches到组件.
     * 默认实现将设置$owner属性并按照events()中声明的那样附加事件处理程序。如果覆盖此方法，请确保实现了父类方法。
     * @param Component $owner 此行为要附加到的组件。
     */
    public function attach($owner)
    {
        $this->owner = $owner;
        foreach ($this->events() as $event => $handler) {
            $owner->on($event, is_string($handler) ? [$this, $handler] : $handler);
        }
    }

    /**
     * 从组件中分离行为对象.
     * 默认实现将取消设置$owner属性并分离events()中声明的事件处理程序。如果覆盖此方法，请确保实现了父类方法。
     * @param $owner
     */
    public function detach()
    {
        if ($this->owner) {
            foreach ($this->events() as $event => $handler) {
                $this->owner->off($event, is_string($handler) ? [$this, $handler] : $handler);
            }
            $this->owner = null;
        }
    }
}