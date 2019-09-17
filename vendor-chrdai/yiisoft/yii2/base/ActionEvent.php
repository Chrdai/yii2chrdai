<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/11
 * Time: 18:08
 */

namespace yii\base;

/**
 * ActionEvent表示用于action事件的事件参数。
 * 通过设置[[isValid]]属性，可以控制是否继续运行操作。
 * Class ActionEvent
 * @package yii\base
 */
class ActionEvent extends Event
{
    /**
     * @var Action 当前正在执行的方法
     */
    public $action;

    /**
     * @var mixed 方法执行后的结果。事件处理程序可以修改此属性以更改操作结果。
     */
    public $result;

    /**
     * @var bool 是否继续运行操作。事件处理程序的[[Controller::EVENT_BEFORE_ACTION]]可以设置此属性来决定是否继续运行当前操作。
     */
    public $isValid = true;

    /**
     * ActionEvent constructor.
     * @param Action $action 与此操作事件关联的方法
     * @param array $config 初始化对象属性的键值对数组
     */
    public function __construct($action, array $config = [])
    {
        $this->action = $action;
        parent::__construct($config);
    }

}