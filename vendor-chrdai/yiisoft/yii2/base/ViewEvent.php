<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/12
 * Time: 18:27
 */

namespace yii\base;

/**
 * ViewEvent表示由[[View]]组件触发的事件。
 * Class ViewEvent
 * @package yii\base
 */
class ViewEvent extends Event
{
    /**
     * @var string 正在呈现的视图文件。
     */
    public $viewFile;

    /**
     * @var array 传递给[[View::render()]方法的参数数组。
     */
    public $params;

    /**
     * @var string [[View::renderFile()]]的呈现结果。
     * 事件处理程序可以修改此属性，修改后的输出将是由[[View::renderFile()]]返回。
     * 此属性仅被[[View::EVENT_AFTER_RENDER]]事件使用。
     */
    public $output;

    /**
     * @var bool 是否继续呈现视图文件。事件处理程序的
     * [[View::EVENT_BEFORE_RENDER]]可以设置此属性来决定是否继续呈现当前视图文件。
     */
    public $isValid = true;
}