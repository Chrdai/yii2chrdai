<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 16:58
 */

namespace yii\web;

use Yii;
use yii\base\Controller;
use yii\base\InvalidRouteException;

class Application extends \yii\base\Application
{
    /**
     * @var string 应用主体的默认路由. 默认值为 'site'.
     */
    public $defaultRoute = 'site';

    /**
     * @var array 配置：指定控制器来处理所有的用户请求。 这主要在应用程序处于维护模式时使用，
     * 通过一个控制器动作来处理所有传入的请求。 此配置是一个数组，其第一个元素指定控制器动作的路径。
     * 其余的数组元素（键值对）指定此动作要绑定的参数 例如，
     *
     * `php
     * [
     *     'offline/notice',
     *     'param1' => 'value1',
     *     'param2' => 'value2',
     * ]
     * `
     * 默认为 null，表示不使用 catch-all 功能。
     */
    public $catchAll;

    /**
     * @var Controller 当前活跃控制器实例
     */
    public $controller;

    public function handleRequest($request)
    {
        if (empty($this->catchAll)) {
            try {
                list($route, $params) = $request->resolve();
                var_dump($route);
                var_dump($params);
            } catch(UrlNormalizerRedirectException $e){
                $url = $e->url;
                if (is_array($url)) {
                    if (isset($url[0])) {
                        //确保url[0]是绝对路径
                        $url[0] = '/' . ltrim($url[0], '/');
                    }
                    $url += $request->getQueryParams();
                }

                //TODO redirect
                //return $this->getResponse()->redirect();
            }
        } else {
            $route = $this->catchAll[0];
            $params = $this->catchAll;
            unset($params[0]);
        }

        try {
            //TODO Yii::debug();
            $this->requestedRoute = $route;
            $result = $this->runAction($route, $params);
            if ($result instanceof Response) {
                var_dump($result);
                return $result;
            }

            $response = $this->getResponse();
            if ($result !== null) {
                $response->data = $result;
            }

            return $response;

        } catch (InvalidRouteException $e) {
            throw new NotFoundHttpException('age not found.', $e->getCode(), $e);
        }

    }

    /**
     * 返回请求组件
     * @return Request 请求组件
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * 返回响应组件
     * @return Response 响应组件
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * {@inheritdoc}
     */
    public function coreComponents()
    {
        return array_merge(parent::coreComponents(), [
            'request' => ['class' => 'yii\web\Request'],
            'response' => ['class' => 'yii\web\Response'],
        ]);
    }
}