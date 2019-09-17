<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/4/26
 * Time: 10:49
 */

namespace yii\web;


use yii\base\BaseObject;

/**
 * @purpose: 为响应设置获取当前请求的http报头。
 *
 * ---
 * 注: IteratorAggregate(聚合式迭代器)是用来将Iterator要求实现的5个方法委托给其它类来实现（比如ArrayIterator）
 *      我自己理解的迭代器就是“一个一个数过去”的意思。
 *      (1)ArrayIterator,允许从PHP数组中创建一个迭代器，ArrayIterator可以直接跳过Iterator需要实现的5个方法，实现同样的功能。
 *      (2)当ArrayIterator和IteratorAggregate一起工作的时候，直接免去Iterator创建方法的工作，只需要在实现IteratorAggregate的getIterator()方法的时候，返回一个ArrayIterator接口就好。
 *      (3)IteratorAggregate的c语言实现代码，定义了抽象方法getIterator()，所以实现接口的时候，必须定义该方法。
 *      (4)因为迭代器都实现了遍历接口（Traversable），所以当我们的HeaderCollection类实现了IteratorAggregate类之后，就可以直接使用foreach()遍历$this->_header中的每一个元素了。
 * ---
 * Class HeaderCollection
 * @package core\web
 */
class HeaderCollection extends BaseObject implements \IteratorAggregate
{
    private $_headers = [];

    public function getIterator()
    {
        return new \ArrayIterator($this->_headers);
    }

    /**
     * @purpose: 获取名称为$name的http报头
     * @param string $name 报头名
     * @param string $default 当所获取的http报头不存的时候，返回的默认值
     * @param bool $first 是否仅仅只返回该报头的第一个值，返回值为一个字符串。如果为flase，将会返回所有，其返回值为一个数组
     * @return mixed|string
     */
    public function get($name, $default = null, $first = true)
    {
        $name = strtolower($name);
        if(isset($this->_headers[$name])){
            return $first ? reset($this->_headers[$name]) : $this->_headers[$name];
        }
        return $default;
    }

    /**
     * @purpose: 设置一个新的http报头
     * @param string $name header名称
     * @param string $value header的值，
     * @return $this
     */
    public function set($name, $value = '')
    {
        $name = strtolower($name);
        $this->_headers[$name] = (array) $value;
        return $this;
    }

    /**
     * @purpose: 设置一个默认的http报头，只有在该http报头不存在的情况下才能设置成功。
     * 如果已经有同名的http报头，则新设置的报头将会被忽略掉。
     * @param string $name header名称
     * @param string $value header的值
     * @return $this
     */
    public function setDefault($name, $value)
    {
        $name = strtolower($name);
        if(empty($this->_headers[$name])){
            $this->_headers[$name][] = $value;
        }
        return $this;
    }

    /**
     * 判断header头是否存在。
     * @param string $name header 头
     * @return bool
     */
    public function has($name)
    {
        $name = strtolower($name);

        return isset($this->_headers[$name]);
    }

    /**
     * 添加新的报头
     * @param string $name 报头名称
     * @param string $value 报头的值
     * @return $this
     */
    public function add($name, $value)
    {
        $name = strtolower($name);
        $this->_headers[$name] = (array) $value;

        return $this;
    }
}