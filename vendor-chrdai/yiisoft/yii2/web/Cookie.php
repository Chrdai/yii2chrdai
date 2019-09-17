<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/5/13
 * Time: 10:24
 */

namespace yii\web;

use yii\base\BaseObject;

class Cookie extends BaseObject
{
    /**
     * @var string cookie的名称
     */
    public $name;

    /**
     * @var string cookie的值
     */
    public $value = '';

    /**
     * @var string cookie过期时间，以服务器时间为准，默认值为0，意思就是cookie的默认过期时间为直到浏览器关闭
     */
    public $expire = 0;

    /**
     * @var string cookie的有效域名
     */
    public $domain = '';

    /**
     * @var string cookie 有效的服务器路径，此处设置的默认值为‘/’时，cookie对整个域名（$demain）有效，
     * 如果设置为‘/foo/’，cookie仅对$domain下的/foo/及其子目录有效。
     */
    public $path = '/';

    /**
     * @var bool 设置cookie是否通过安全的https链接传给客户端，如果设置为true，只有安全链接存在时才设置cookie ，此处默认这设置为 false
     */
    public $secure = false;

    /**
     * @var bool 如果设置为true,cookie仅可通过http协议访问，不能通过类似于Javascript之类的脚本语言访问，可以有效的减少xss攻击时的身份窃取行为。
     */
    public $httpOnly = true;
    
}
