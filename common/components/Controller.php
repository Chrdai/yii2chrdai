<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/3/28
 * Time: 15:55
 */

namespace common\components;


use yii\i18n\Formatter;
use yii\web\Response;

class Controller extends \yii\web\Controller
{
    /**
     * 返回ajax消息
     * @param int $status 状态码
     * @param string $message 消息体
     * @throws \yii\base\ExitException
     */
    public function ajaxReturn($status, $message)
    {
        $response = \Yii::$app->getResponse();
        $response->data = [
            'status' => $status,
            'message' => $message,
        ];
        $response->format = Response::FORMAT_JSON;
        $response->send();
        \Yii::$app->end(); //$response->send() 一定要终止程序的执行, 否则后续的代码还会被执行
    }
}