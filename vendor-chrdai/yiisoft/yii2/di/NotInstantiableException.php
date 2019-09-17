<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 11:58
 */

namespace yii\di;


use yii\base\InvalidConfigException;

/**
 * NotInstantiableException 表示由不正确的依赖项注入容器配置或使用引起的异常。
 * Class NotInstantiableException
 * @package yii\base
 */
class NotInstantiableException extends InvalidConfigException
{
    /**
     * {@inheritdoc}
     */
    public function __construct($class, $message = null, $code = 0, \Exception $previous = null)
    {
        if ($message === null) {
            $message = "Can not instantiable $class";
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string 用户友好
     */
    public function getName()
    {
        return 'Not instantiable';
    }
}