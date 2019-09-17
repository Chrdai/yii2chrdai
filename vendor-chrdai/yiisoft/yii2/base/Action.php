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
 * Action是所有控制器动作类的基类。
 * Action提供了一种重用Action方法代码的方法。动作中的动作方法类可用于多个控制器或不同的项目。
 * 派生类必须实现一个名为' run() '的方法。这个方法将在请求操作时由控制器调用。
 * 'run()'方法可以有将被填充的参数,例如，如果' run() '方法声明如下:
 *
 * ```php
 * public function run($id, $type = 'book') { ... }
 * ```
 * 如果为操作提供的参数是:' ['id' => 1] '，“run()”方法将被自动调用为“run(1)”。
 *
 * @property string $uniqueId 此操作在整个应用程序中的唯一ID。此属性为只读属性。
 * Class Action
 * @package yii\base
 */
class Action extends Component
{
    /**
     * @var string 方法ID
     */
    public $id;

    /**
     * @var Controller|\yii\web\Controller|\yii\console\Controller 拥有此Action的控制器
     */
    public $controller;

    /**
     * Action constructor.
     * @param string $id 方法ID
     * @param Controller $controller 拥有此Action的控制器
     * @param array $config 初始化对象属性的键值对
     */
    public function __construct($id, $controller, array $config = [])
    {
        $this->id = $id;
        $this->controller = $controller;
        parent::__construct($config);
    }

    /**
     * 返回此操作在整个应用程序中的唯一ID。
     * @return string 此操作在整个应用程序中的唯一ID。
     */
    public function getUniqueId()
    {
        return $this->controller->getUniqueId() . '/' . $this->id;
    }

    /**
     * 使用指定的参数运行此操作,此方法主要由控制器调用。
     * @param array $params 要绑定到操作的run()方法的参数。
     * @return mixed|null 执行后的返回结果
     * @throws InvalidConfigException 如果action类没有run()方法抛出异常
     */
    public function runWithParams($params)
    {
        if (!method_exists($this, 'run')) {
            throw new InvalidConfigException(get_class($this) . ' must define a "run()" method.');
        }
        $args = $this->controller->bindActionParams($this, $params);
        //TODO Yii::debug();
        if (Yii::$app->requestedParams === null) {
            Yii::$app->requestedParams = $args;
        }
        if ($this->beforeRun()) {
            $result = call_user_func_array([$this, 'run'], $args);
            $this->afterRun();

            return $result;
        }

        return null;
    }

    /**
     * 这个方法在执行run()之前被调用。
     * 您可以覆盖此方法来为操作运行做准备工作。
     * 如果方法返回false，它将取消操作。
     * @return bool
     */
    public function beforeRun()
    {
        return true;
    }

    /**
     * 在执行'run()'之后立即调用此方法。
     * 可以覆盖此方法来为操作运行做后处理工作。
     */
    public function afterRun()
    {

    }
}