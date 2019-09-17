<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 11:58
 */

namespace yii\base;

/**
 * UnknownPropertyException表示由于访问未知对象属性而导致的异常。
 * Class UnknowPropertyException
 * @package yii\base
 */
class UnknownPropertyException extends Exception
{
    /**
     * @return string 用户友好
     */
    public function getName()
    {
        return 'Invalid Configuration';
    }
}