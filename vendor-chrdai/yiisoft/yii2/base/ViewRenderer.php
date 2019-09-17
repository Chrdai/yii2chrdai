<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/12
 * Time: 18:40
 */

namespace yii\base;

/**
 * ViewRenderer是视图渲染器类的基类。
 * Class ViewRenderer
 * @package yii\base
 */
abstract class ViewRenderer extends Component
{
    /**
     * 呈现视图文件。
     *
     * 每当[[View]]尝试呈现视图时，就会调用该方法。子类必须实现此方法来呈现给定的视图文件。
     * @param View $view 用于呈现文件的视图对象。
     * @param string $file 视图文件
     * @param array $params 要传递到视图文件的参数。
     * @return string 渲染的结果
     */
    abstract public function render($view, $file, $params);
}