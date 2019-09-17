<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/17
 * Time: 9:17
 */

namespace yii\base;


class Response extends Component
{
    /**
     * @var int 退出状态。退出状态应该在0到254之间。状态0表示程序成功终止。
     */
    public $exitStatus = 0;
}