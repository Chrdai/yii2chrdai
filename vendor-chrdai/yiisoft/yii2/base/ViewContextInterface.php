<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/5
 * Time: 18:53
 */

namespace yii\base;

/**
 * ViewContextInterface是希望支持相对视图名称的类应该实现的接口。
 * Interface ViewContextInterface
 * @package yii\base
 */
interface ViewContextInterface
{
    /**
     * @return string 获取视图的相对路径。
     */
    public function getViewPath();
}