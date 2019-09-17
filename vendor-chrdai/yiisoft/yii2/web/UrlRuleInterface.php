<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/12
 * Time: 10:03
 */

namespace yii\web;

/**
 * UrlRuleInterface应该被 url 和 rules 相关类实现、
 * Interface UrlRuleInterface
 * @package yii\web
 */
interface UrlRuleInterface
{
    /**
     * 解析给定的请求，并返回请求路由和请求参数
     * @param UrlManager $manager Url管理器
     * @param Request $request 请求组件
     * @return array|bool 解析的结果，请求路由和请求参数会以数组的形式返回，
     * 如果返回false，标识该规则不能正确解析该url。
     */
    public function parseRequest($manager, $request);

    /**
     * 根据给定的路由和参数创建一个URL。
     * @param UrlManager $manager Url管理器
     * @param string $route 路由，在开头和结尾不能有斜杠。
     * @param array $params 参数
     * @return string|bool 创建好的路url地址，如果返回false，代表规定的路由和参数不能成功创建url。
     */
    public function createUrl($manager, $route, $params);
}