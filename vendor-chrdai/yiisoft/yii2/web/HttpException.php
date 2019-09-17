<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/9
 * Time: 9:41
 */

namespace yii\web;


use yii\base\UserException;

class HttpException extends UserException
{
    /**
     * @var int Http状态码，如 404
     */
    public $statusCode;

    /**
     * HttpException constructor.
     * @param string $status http状态码
     * @param null $message 错我消息
     * @param int $code 错误码
     * @param \Exception|null $previous 用于异常链接的前一个异常。
     */
    public function __construct($status, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->statusCode = $status;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string 用户友好
     */
    public function getName()
    {
        if (isset(Response::$httpStatuses[$this->statusCode])) {
            return Response::$httpStatuses[$this->statusCode];
        }
        return 'Error';
    }
}