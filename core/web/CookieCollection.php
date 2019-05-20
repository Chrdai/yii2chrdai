<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/5/13
 * Time: 10:24
 */

namespace core\web;


use core\base\BaseObject;

/**
 *  * @purpose: 维护当前请求中的cookie
 *
 * ---
 * 注: IteratorAggregate(聚合式迭代器)是用来将Iterator要求实现的5个方法委托给其它类来实现（比如ArrayIterator）
 *      我自己理解的迭代器就是“一个一个数过去”的意思。
 *      (1)ArrayIterator,允许从PHP数组中创建一个迭代器，ArrayIterator可以直接跳过Iterator需要实现的5个方法，实现同样的功能。
 *      (2)当ArrayIterator和IteratorAggregate一起工作的时候，直接免去Iterator创建方法的工作，只需要在实现IteratorAggregate的getIterator()方法的时候，返回一个ArrayIterator接口就好。
 *      (3)IteratorAggregate的c语言实现代码，定义了抽象方法getIterator()，所以实现接口的时候，必须定义该方法。
 *      (4)因为迭代器都实现了遍历接口（Traversable），所以当我们的HeaderCollection类实现了IteratorAggregate类之后，就可以直接使用foreach()遍历$this->_header中的每一个元素了。
 * ---
 * Class CookieCollection
 * @package core\web
 */
class CookieCollection extends BaseObject implements \IteratorAggregate
{
    /**
     * @var bool 设置cookie是否为只读属性
     */
    public $readOnly = false;

    /**
     * @var array 该数组集合中的cookie，（以数组名称排序）
     */
    private $_cookies = [];

    /**
     * CookieCollection constructor.
     * @param array $cookies cookie初始化时所包含的cookie,必须是key=>value的格式的数组
     * @param array $config key=>value格式的数组，用来初始化对象的属性。
     */
    public function __construct($cookies = [], $config = [])
    {
        $this->_cookies = $cookies;

        parent::__construct($config);
    }

    /**
     * @purpose: 返回一个遍历cookie数组的迭代器
     * 注：1、该方法是php标准库SPL中IteratorAggregate接口的抽象方法。
     *    2、当使用foreach遍历$this->_cookie的时候，会被隐式的自动调用。
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->_cookies);
    }

    /**
     * @purpose: 返回指定名称的cookie
     * @param string $name cookie名称
     * @return Cookie|null 返回指定名称的cookie(core\web\Cookie对象)，如果没有设置名称，将会返回null。
     */
    public function get($name)
    {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name] : null;
    }

    /**
     * @purpose: 返回指定名称cookie的值
     * @param string $name cookie名称
     * @param null $defaultValue 当该cookie名称不存在的时候，返回的默认值
     * @return mixed
     * @see get()
     */
    public function getValue($name, $defaultValue = null)
    {
        return isset($this->_cookies[$name]) ? $this->_cookies[$name]->value : $defaultValue;
    }

    /**
     * @purpose: 判断特定name的cookie是否存在
     * 注；如果某个cookie被浏览器删除了，该方法将会放回false
     * @param string $name cookie名称
     * @return bool 该cookie是否已存在
     * @see remove()
     */
    public function has($name)
    {
        return isset($this->_cookies[$name]) && $this->_cookies[$name]->value !== ''
            && $this->_cookies[$name]->expire === null || $this->_cookies[$name]->expire === 0 || $this->_cookies[$name]->expire >= time();
    }

    /**
     * @purpose: 添加一个cookie
     * 注；如果已经存在相同名字的cookie，将会把相同名字的cookie删除掉
     * @param string $name 即将添加的cookie名称
     * @throws \Exception 当cookie为只读属性时，将会抛出一个异常
     */
    public function add($cookies)
    {
        if ($this->readOnly) {
            throw new \Exception("该cookie集合为只读属性");
        }
        $this->_cookies[$cookies->name] = $cookies;
    }

    /**
     * @purpose: 删除cookie
     * 注：如果 $removeFromBrowser 为true,则该cookie将会从集合中移除，
     *    在这种情况下，一个过期的cookie的将会被添加到集合中。
     * @param Cookie|string $cookie 即将被移除的cookie对象或者cookie名称
     * @param bool $removeFromBrowser 是否将cookie从浏览器中删除
     * @throws \Exception 当cookie为只读属性时，将会抛出一个异常
     */
    public function remove($cookie, $removeFromBrowser = true)
    {
        if ($this->readOnly) {
            throw new \Exception("该cookie集合为只读属性");
        }
        if ($cookie instanceof Cookie) {
            $cookie->value = '';
            $cookie->expire = 1;
        } else {

        }
        if ($removeFromBrowser) {
            $this->_cookies[$cookie->name] = $cookie;
        } else {
            unset($this->_cookies[$cookie->name]);
        }
    }

    /**
     * @purpose: 删除所有的cookie
     * @throws \Exception 当cookie为只读属性时，将会抛出一个异常
     */
    public function removeAll()
    {
        if ($this->readOnly) {
            throw new \Exception("该cookie集合为只读属性");
        }
        $this->_cookies = [];
    }


    /**
     * @purpose: 将cookie集合作为一个数组返回
     * @return array 所返回的集合中 keys 即为cookie集合的名称， values 为想用的cookie对象
     */
    public function toArray()
    {
        return $this->_cookies;
    }
}