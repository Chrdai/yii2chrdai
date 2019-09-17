<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 11:58
 */

namespace yii\base;


/**
 * InvalidCallException表示以错误的方式调用方法导致的异常
 * Class InvalidCallException
 * @package yii\base
 */
class InvalidCallException extends \BadMethodCallException
{
    /**
     * @return string 用户友好
     */
    public function getName()
    {
        return 'Invalid Call';
    }
}