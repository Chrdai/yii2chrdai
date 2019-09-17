<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 11:58
 */

namespace yii\base;

/**
 * ExitException 表示应用程序的正常终止。
 * 不要捕获 ExitException。Yii 将处理此异常以优雅地终止应用程序。
 * Class ExitException
 * @package yii\base
 */
class ExitException extends Exception
{
    /**
     * @var int 退出的状态码
     */
    public $statusCode;

    /**
     * ExitException constructor.
     * @param int $status 退出的状态码
     * @param null $message 错误消息
     * @param int $code 错误码
     * @param \Exception|null $previous 异常链接的前一个异常。
     */
    public function __construct($status = 0, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->status = $status;
        parent::__construct($message, $code, $previous);
    }
}