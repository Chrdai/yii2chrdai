<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 17:15
 */

namespace yii\web;


/**
 * 配置接口，具体配置由继承它的类来实现。初始化类的属性是用类的最后一个参数来初始化的
 *
 * 该接口没有方法，继承该接口的类构造函数必须是如下形式：
 *
 * ```php
 * public function __constructor($param1, $param2, ..., $config = [])
 * ```
 *
 * Interface Configurable
 * @package yii\web
 */
interface Configurable
{

}