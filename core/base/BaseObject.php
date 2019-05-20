<?php
/**
 * 一个基类，主要对PHP的魔术方法进行了复写，方便其他类调用该基类使用
 * User: daicr
 * Date: 2019/4/2
 * Time: 9:44
 */

namespace core\base;


class BaseObject
{
    /**
     * BaseObject constructor.
     * @purpose: 以 key=>value的形式给类的属性赋值
     * 注意：1、如果需要继承该类，请将 $config 写在最后一个参数，像这样：
     * ```php
     * public function __construct($params1, $params2, $config = [])
     * {
     *      ....
     *      parent::__construct($config);
     * }
     * ```
     * @param array $config 请使用 $key => $value 的形式
     */
    public function __construct(array $config = [])
    {
        foreach($config as $name => $value){
            if(property_exists($this, $name)){
                $this->$name = $value;
            }
        }
        $this->init();
    }

    /**
     * @purpose: 初始化对象
     */
    public function init()
    {
    }

    /**
     * @prupose: 返回调用对象类名的全路径
     * 如果 version > php5.5 , 可以使用 `::class` 代替
     * @return string
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * @purpose: 返回一个对象的属性
     * 这是php的魔术方法，请不要直接调用，当类的属性没有显示定义的时候，该方法会被隐式调用 `$value = $object->property`
     * @param string $name 属性名
     * @return mixed 属性值
     * @throws \Exception
     */
    public function __get($name)
    {
        $getter = 'get' . $name;
        if(method_exists($this, $getter)){
            return $this->$getter();
        }elseif(method_exists($this, 'set' . $name)){
            throw new \Exception("Getting write-only property " . get_class($this) . ":: " . $name);
        }else{
            throw new \Exception("Getting unknown property " . get_class($this) . ":: " . $name);
        }
    }

    /**
     * @purpose: 给对象的属性设置值
     * 这是php的魔术方法，请不要直接调用，当类的属性没有显示定义的时候，该方法会被隐式调用 `$object->property = $value`
     * @param string $name 属性名
     * @param mixed $value 属性值
     * @return mixed
     * @throws \Exception
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if(method_exists($this, $setter)){
            return $this->$setter($value);
        }elseif(method_exists($this, 'get' . $name)){
            throw new \Exception("Getting read-only property " . get_class($this) . ":: " . $name);
        }else{
            throw new \Exception('Getting unknown property '. get_class($this) . ":: " . $name);
        }
    }

    /**
     * @purpose: 判断某个属性是否存在
     * 这是php的魔术方法，请不要直接调用，当类的属性没有显示定义的时候，该方法会被隐式调用 `isset($object->property)`
     * @param string $name 属性名
     * @return bool true|false
     */
    public function __isset($name)
    {
        $getter = 'get' . $name;
        if(method_exists($this, $getter)){
            return $this->$getter() !== null;
        }
        return false;
    }

    /**
     * @purpose: 将某个属性的值为 null
     * 这是php的魔术方法，请不要直接调用，当类的属性没有显示定义的时候，该方法会被隐式调用 `unset($object->property)`
     * @param string $name 属性名
     * @throws \Exception
     */
    public function __unset($name)
    {
        $setter = 'set' . $name;
        if(method_exists($this, $setter)){
            $this->$setter(null);
        }elseif(method_exists($this, 'get' . $name)){
            throw new \Exception('Getting read-only property ' . get_class($this) . ':: ' . $name);
        }
    }

    /**
     * 这是php的魔术方法，请不要直接调用，当类的方法不存在的时候，该方法会被隐式调用
     * @param string $name 方法名
     * @param array $arguments 参数
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        throw new \Exception('Calling unknow method ' . get_class($this) . ':: ' . $name);
    }

    /**
     * @purpose: 判断属性某个属性是否存在
     *
     * ```
     * 满足如下情况，则属性存在：
     * 1、类中存在与指定的名称关联的`getter` 或者`setter`方法
     * 2、类中原来就存在的成员变量 (当$checkVars = true时)
     * ```
     * @param string $name 属性名
     * @param bool $checkVars 是否将成员变量当做属性
     * @return bool true|false
     */
    public function hasProperty($name, $checkVars = true)
    {
        return $this->canGetProperty($name, $checkVars) || $this->canSetProperty($name, false);
    }

    /**
     * @purpose: 判断某个属性是否为可读属性
     *
     * ```
     * 满足如下情况，则为可读属性：
     * 1、类中存在与指定的名称关联的`getter`方法
     * 2、类中原来就存在的成员变量 (当$checkVars = true时)
     * ```
     * @param string $name 属性名
     * @param bool $checkVars 是否将成员变量当做属性
     * @return bool true|false
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'get' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * @purpose: 判断某个属性是否为可写属性
     *
     * ```
     * 满足如下情况，则为可写属性：
     * 1、类中存在与指定的名称关联的`setter`方法
     * 2、类中原来就存在的成员变量 (当$checkVars = true时)
     * ```
     * @param string $name 属性名
     * @param bool $checkVars 是否将成员变量当做属性
     * @return bool true|false
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return method_exists($this, 'set' . $name) || $checkVars && property_exists($this, $name);
    }

    /**
     * @purpose: 判断某个方法是否存在
     * @param string $name 方法名
     * @return bool true|false
     */
    public function hasMethod($name)
    {
        return method_exists($this, $name);
    }
}