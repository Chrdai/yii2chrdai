<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/10
 * Time: 18:00
 */

namespace yii\base;

use Yii;

/**
 * InlineAction表示定义为控制器方法的操作。
 * 控制器方法的名称可以通过[[actionMethod]]获得,由创建此操作的[[controller]]设置。
 * Class InlineAction
 * @package yii\base
 */
class InlineAction extends Action
{
    /**
     * @var string 与此内联操作关联的控制器方法
     */
    public $actionMethod;

    /**
     * InlineAction constructor.
     * @param string $id Action的ID
     * @param Controller $controller 拥有此Action的控制器
     * @param string $actionMethod 与此内联操作关联的控制器方法
     * @param array $config 初始化对象属性的键值对
     */
    public function __construct($id, Controller $controller, $actionMethod, array $config = [])
    {
        $this->actionMethod = $actionMethod;
        parent::__construct($id, $controller, $config);
    }

    /**
     * 使用指定的参数运行此Action, 此方法主要由控制器调用。
     * @param array $params 所调用方法的参数
     * @return mixed action的返回结果
     */
    public function runWithParams($params)
    {
        $args = $this->controller->bindActionParams($this, $params);
        //TODO Yii::debug
        if (Yii::$app->requestedParams === null) {
            Yii::$app->requestedParams = $args;
        }
        return call_user_func_array([$this->controller, $this->actionMethod], $args);
    }
}