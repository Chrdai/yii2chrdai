<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 18:35
 */

namespace yii\base;

use Yii;
use yii\di\ServiceLocator;

/**
 * Module 是模块和应用程序类的基类
 *
 * 模块表示子应用程序，子应用程序本身包含MVC元素，如*模型、视图、控制器等
 * 一个模块可以由 [模块|子模块] 组成
 *
 * 组件可以在模块中注册，以便在模块中全局访问它们
 *
 * Class Module
 * @package yii\base
 */
class Module extends ServiceLocator
{
    /**
     * @event ActionEvent 在执行控制器动作之前触发的事件。
     * 可以将[[ActionEvent::isValid]]设置为' false '来取消动作执行。
     */
    const EVENT_BEFOR_ACTION = 'beforeAction';

    /**
     * @event ActionEvent 在执行控制器操作后触发的事件
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * @var string 在具有相同[[父模块]]的其他模块中唯一标识此模块的ID
     */
    public $id;

    /**
     * @var Module 当前模块儿的父模块儿，如果为 null ,意思是当前模块儿没有父模块儿
     */
    public $module;

    /**
     * @var string|bool 应该应用于此模块中的视图的布局。这引用视图名称相对于[[layoutPath]]。
     * 如果不设置，则表示[[模块|父模块]的layout值]将被使用。如果为‘false'，布局将在这个模块被禁用。
     */
    public $layout;

    /**
     * @var array 存放该模块的子模块
     */
    public $_modules = [];

    /**
     * @var string 模块儿的根目录
     */
    private $_basePath;

    /**
     * @var string 视图文件目录，包含此模块视图文件的根目录
     */
    private $_viewPath;

    /**
     * @var string layout文件目录，包含此模块布局视图文件的根目录。
     */
    private $_layoutPath;

    /**
     * @var string 控制器类的命名空间
     * 如果没有设置，就使用模块儿名下的 controllers。
     * 例如： 模块儿名是 foo\bar ，那么控制的默认的命名空间就是 foo\bar\controllers
     */
    public $controllerNamespace;

    /**
     * @var array  已加载模块数组，类名作为 key
     */
    public $loadedModules = [];

    /**
     * @var string 模块儿的默认路由，默认值为 'default'
     * 路由可能包含，子模块ID,控制器ID和方法名。
     * 如:`help`, `post/create`, `admin/post/create`
     * 如果方法名未给定，将使用该值作为默认的方法名[[Controller::defaultAction]].。
     */
    public $defaultRoute = 'default';

    /**
     * @var array 从控制器ID到控制器配置的数组映射。
     * 每个键值对指定单个控制器的配置。控制器配置可以是字符串，也可以是数组。
     * 如果是字符串，则字符串应该是控制器的完全限定类名。如果是数组，则数组必须包含一个指定的“class”元素
     * 控制器的完全限定类名，以及其余的键值对用于初始化相应的控制器属性。例如,
     *
     * ```php
     * [
     *   'account' => 'app\controllers\UserController',
     *   'article' => [
     *      'class' => 'app\controllers\PostController',
     *      'pageTitle' => 'something new',
     *   ],
     * ]
     * ```
     */
    public $controllerMap = [];


    /**
     * Module constructor.
     * @param string $id 当前模块儿的id
     * @param null $parent 当前模块儿的父模块儿
     * @param array $config 键值对形式的数组，用于初始化对象的属性值
     */
    public function __construct($id, $parent = null, array $config = [])
    {
        $this->id = $id;
        $this->module = $parent;
        parent::__construct($config);
    }

    /**
     * @purpose: 初始化模块儿
     */
    public function init()
    {
        if ($this->controllerNamespace == null) {
            //返回对象的类名,包含命名空间
            $class = get_class($this);
            if (($pos = strrpos($class, '\\') !== false)) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }
    }

    /**
     * *返回一个ID，该ID在当前应用程序中的所有模块中唯一标识此模块。
     * 注意，如果模块是一个应用程序，将返回一个空字符串。
     * @return string 模块的唯一ID。
     */
    public function getUniqueId()
    {
        return $this->module ? ltrim($this->module->getUniqueId() . '/' . $this->id, '/') : $this->id;
    }

    /**
     * 设置此模块类的当前请求实例
     * @param object $instance 此模块类当前请求的实例。如果为 null ，则删除调用类的实例
     */
    public static function setInstance($instance)
    {
        if ($instance === null) {
            unset(Yii::$app->loadedModules[get_called_class()]);
        } else {
            Yii::$app->loadedModules[get_class($instance)] = $instance;
        }
    }

    /**
     * 设置应用程序的根目录
     * @param string $path 应用程序的根目录
     * @throws InvalidArgumentException 如果目录不存在抛出异常
     */
    public function setBasePath($path)
    {
        $path = Yii::getAlias($path);
        $p = strncmp($path, 'phar://', 7) === 0 ? $path : realpath($path);
        if ($p != false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new InvalidArgumentException("The directory does not exist: $path");
        }
    }

    /**
     * @purpose: 返回模块儿的根目录
     * 默认为包含模块类文件的目录
     * @return string
     */
    public function getBasePath()
    {
        if ($this->_basePath === null) {
            //报告类的有关信息。
            $class = new \ReflectionClass($this);
            $this->_basePath = dirname($class->getFileName()); //获取定义类的文件名所在目录
        }
        return $this->_basePath;
    }

    /**
     * {@inheritdoc}
     *
     * @param string $id 组件 ID（e.g. db）。
     * @param bool $throwException 如果 $id 之前未在定位器中注册，是否抛出一个异常。
     * @return mixed|null 指定 ID 的组件。如果 $throwException 为 false 并且 $id 在之前没有被注册，将会返回 null。
     */
    public function get($id, $throwException = true)
    {
        if (!isset($this->module)) {
            return parent::get($id, $throwException);
        }

        $component = parent::get($id, false);
        if ($component === null) {
            $component = $this->module->get($id, $throwException);
        }
        return $component;
    }

    /**
     * 返回一个值，该值表示定位器是否具有指定的组件定义或是否已实例化该组件。 此方法根据 $checkInstance 的值返回不同的结果。
     * 如果 $checkInstance 为 false（default）， 此方法将返回一个表示定位器是否具有指定组件定义的值。
     * 如果 $checkInstance 为 true， 此方法会返回一个表示定位器是否已经实例化指定组件的值。
     * 从 version 2.0.13开始，该方法将只存在于父类中。
     * @param string $id 组件 ID（e.g. db）。
     * @param bool $checkInstance 是否应检查组件是否已共享和实例化。
     * @return bool 是否定位器具有指定的组件定义或已实例化组件。
     */
    public function has($id, $checkInstance = false)
    {
        return parent::has($id, $checkInstance) || isset($this->module) && $this->module->has($id, $checkInstance);
    }

    /**
     * 运行由路由指定的控制器方法。
     * 此方法解析指定的路由并创建相应的子模块、控制器和操作例。然后它调用[[Controller::runAction()]]来运行具有给定参数的操作。
     * 如果路由为空，该方法将使用[[defaultRoute]]。
     * @param string $route 指定方法的路由
     * @param array $params 要传递给方法的参数
     * @return mixed 方法执行后的返回结果
     * @throws InvalidRouteException 如果请求的路由不能成功解析为方法
     */
    public function runAction($route, $params = [])
    {
        $parts = $this->createController($route);
//        var_dump($parts);
        if (is_array($parts)) {
            /* @var $controller Controller */
            list($controller, $actionId) = $parts;
            $oldController = Yii::$app->controller;
            $result = $controller->runAction($actionId, $params);

            if ($oldController !== null) {
                Yii::$app->controller = $oldController;
            }

            return $result;
        }

        $id = $this->getUniqueId();
        throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
    }

    /**
     * 根据给定的路由创建控制器实例。
     *
     * 路由应该相对于这个模块。该方法实现以下算法
     * 解决指定路线:
     * 1。如果路由是空的，使用[[defaultRoute]];
     * 2。如果路由的第一个段是[[modules]]中声明的有效模块ID，调用模块的“createController()”和路由的其余部分;
     * 3。如果在[[controllerMap]]中找到路由的第一个段，则创建一个控制器基于[[controllerMap]]中找到的对应配置;
     * 4。所给路由的格式是“abc/def/xyz”。或者“abc\DefController”或[[controllerNamespace|controller namespace]]中的' abc\def\XyzController '类。
     *
     * 如果上面的任何一个步骤分解为一个控制器，它将与其他步骤一起返回
     * 部分路由将被视为操作ID，否则将返回“false”。
     * @param string $route 由模块、控制器和动作id组成的路由。
     * @return array|bool 如果控制器创建成功，它将一起返回使用请求的操作ID。否则将返回“false”。
     */
    public function createController($route)
    {
        if ($route == '') {
            $route = $this->defaultRoute;
        }

        //两个`/`和开头结尾的`/`都会引起substr截取的值不准确。
        $route = trim($route, '/');
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            list($id, $route) = explode('/', $route, 2);
        } else {
            $id = $route;
            $route = '';
        }

        // module and controller map take precedence
        if(isset($this->controllerMap[$id])) {
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }
        $module = $this->getModule($id);
        if ($module !== null) {
            return $module->createController($route);
        }

        if ($pos = strpos($route, '/') !== false ) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }

        $controller = $this->createControllerById($id);
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerById($id . '/'. $route);
            $route = '';
        }

        return $controller === null ? false : [$controller, $route];
    }

    /**
     * 根据给定的控制器ID创建控制器。
     * 控制器ID是相对于这个模块的
     * 应该在[[controllerNamespace]]下使用命名空间。
     *
     * 注意，此方法不检查[[modules]]或[[controllerMap]]。
     * @param string $id 控制器ID
     * @return mixed|null|object 控制器|null新创建的控制器实例，如果控制器ID无效则为“null”。
     * @throws InvalidConfigException 如果控制器类与其文件名不匹配。此异常仅在调试模式下抛出。
     */
    public function createControllerById($id)
    {
        $pos = strpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }

        if ($this->isIncorrectClassNameOrPrefix($className, $prefix)) {
            return null;
        }

        $className = preg_replace_callback('%-([a-z0-9_])%i', function($matchs){
            return ucfirst($matchs[1]);
        }, ucfirst($className)) . 'Controller';
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix) . $className, '\\');
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }

        if (is_subclass_of($className, 'yii\base\Controller')) {
            $controller = Yii::createObject($className, [$id, $this]);
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException('Controller class must extend from \\yii\\base\\Controller.');
        }

        return null;
    }

    /**
     * 检查类名或前缀是否正确
     * @param string $className 类名
     * @param string $prefix 前缀
     * @return bool
     */
    public function isIncorrectClassNameOrPrefix($className, $prefix)
    {
        if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
            return true;
        }
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return true;
        }

        return false;
    }

    /**
     * 检索指定ID的子模块。
     * 此方法支持同时检索子模块和子孙模块。
     * @param string $id 模块ID，（大小写敏感）要检索子孙模块，使用与此模块相关的ID路径(例如。“admin/content”)。
     * @param bool $load 如果模块尚未加载，是否加载该模块。默认为true
     * @return null|Module 模块实例，如果模块不存在，则为“null”。
     */
    public function getModule($id, $load = true)
    {
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0 , $pos));

            return $module === null ? null : $module->getModule(substr($id, $pos + 1), $load);
        }
        if (isset($this->_modules[$id])) {
            if ($this->_modules[$id] instanceof self) {
                return $this->_modules[$id];
            } elseif ($load) {
                //TODO Yii::debug
                /* @var $module Module */
                $module = Yii::createObject($this->_modules[$id], [$id, $this]);
                $module->setInstance($module);
                return $this->_modules[$id] = $module;
            }
        }

        return null;
    }

    /**
     * 此方法在执行此模块中的操作之前调用。
     * 该方法将触发[[EVENT_BEFORE_ACTION]]事件。方法的返回值将决定操作是否应该继续运行。
     * 如果方法不应该运行，请求应该在“beforeAction”代码中处理
     * 提供必要的输出或重定向请求。否则响应将为空。
     * 如果你重写了这个方法，你的代码应该如下:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     * @param Action $action 待执行的方法
     * @return bool 方式是否继续往下执行
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFOR_ACTION, $event);
        return $event->isValid;
    }

    /**
     * 此方法在执行操作之后立即调用。
     * 该方法将触发[[EVENT_AFTER_ACTION]]事件。方法的返回值将用作操作返回值。
     * 如果你重写了这个方法，你的代码应该如下:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action 当前正在执行的方法
     * @param mixed $result 方法的返回值
     * @return mixed 方法处理后的结果。
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }

    /**
     * 返回包含此模块视图文件的目录
     * @return string 视图文件的根目录。默认为“[[basePath]]/views”。
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        }

        return $this->_viewPath;
    }

    /**
     * 设置包含视图文件的目录
     * @param string $path 视图文件的根目录。
     * @throws InvalidArgumentException 如果目录无效
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**
     * @return string 返回包含此模块布局视图文件的目录。
     */
    public function getLayoutPath()
    {
        if ($this->_layoutPath === null) {
            $this->_layoutPath = $this->getViewPath() . DIRECTORY_SEPARATOR . 'layouts';
        }

        return $this->_layoutPath;
    }

    /**
     * 设置包含布局文件的目录。
     * @param string $path 布局文件的根目录或[路径别名]。
     */
    public function setLayoutPath($path)
    {
        $this->_layoutPath = Yii::getAlias($path);
    }
}