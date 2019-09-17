<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 19:01
 */

namespace yii\base;

use Yii;
use yii\web\Request;
use yii\web\Response;

/**
 * Application是所有应用程序类的基类
 * Class Application
 * @package yii\base
 */
abstract class Application extends Module
{
    /**
     * @event Event 在应用程序开始处理请求之前引发的事件
     */
    const EVENT_BEFORE_REQUEST = 'beforeRequest';

    /**
     * @event Event 应用程序成功处理请求后(在发出响应之前)引发的事件。
     */
    const EVENT_AFTER_REQUEST = 'afterRequest';

    /**
     * 应用程序启动状态
     */
    const STATE_BEGIN = 0;

    /**
     * 应用程序触发状态
     */
    const STATE_BEFORE_REQUEST = 2;

    /**
     * 应用程序开始处理请求的状态
     */
    const STATE_HANDLING_REQUEST = 3;

    /**
     * 应用程序正在触发[[请求后的事件]]..
     */
    const STATE_AFTER_REQUEST = 4;

    /**
     * 用程序即将发送响应。
     */
    const STATE_SENDING_RESPONSE = 5;

    /**
     * 应用程序已经结束
     */
    const STATE_END = 6;

    /**
     * @var int 请求处理生命周期中的当前应用程序状态。此属性由应用程序管理。请不要修改此属性
     */
    public $state;

    /**
     * @var string 应用程序的编码
     */
    public $charset = 'UTF-8';

    /**
     * @var string 用于最终用户的语言。建议你使用[IETF语言标记](http://en.wikipedia.org/wiki/IETF_language_tag)。例如，' en '代表代表英语，而en-US代表英语(美国)。
     * @see language
     */
    public $language = 'en-us';

    /**
     * @var string 应用程序所使用的语言。这主要是指写入消息和视图文件的语言。
     * @see language
     */
    public $sourceLanguage = 'en-us';

    /**
     * @var string 请求的路由
     */
    public $requestedRoute;

    /**
     * @var array 为请求的操作提供的参数
     */
    public $requestedParams;

    /**
     * @var Action 请求的方法，如果为null,表示不能将请求解析为相应的方法
     */
    public $requestedAction;

    /**
     * @var string 控制器类所在的名称空间。这个名称空间将通过将其前置到控制器类名来加载控制器类。
     * 默认名称空间是' app\controllers '。
     */
    public $controllerNamespace = 'app\\controllers';

    /**
     * @var Controller 当前活跃控制器实例
     */
    public $controller;

    /**
     * @var string|bool 应用程序中应用于视图的布局。默认为“main”。如果是 false，布局将被禁用。
     */
    public $layout = 'main';


    public function __construct(array $config = [])
    {
        Yii::$app = $this;
        static::setInstance($this);

        $this->state = self::STATE_BEGIN;

        $this->preInit($config);

        //$this->registerErrorHandler($config);

        Component::__construct($config);
    }

    public function run()
    {
        try {
            $this->state = self::STATE_BEFORE_REQUEST;
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            $response = $this->handleRequest($this->getRequest());

            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);

            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();

            $this->state = self::STATE_END;

            return $response->exitStatus;

        } catch (ExitException $e) {
            var_dump($e->getMessage());
        }
    }

    /**
     * 处理指定的请求。
     * 这个方法应该返回一个[[Response]]实例或它的子类，这个子类表示请求的处理结果。
     * @param Request $request 待处理的请求
     * @return Response 响应节节高
     */
    abstract public function handleRequest($request);

    /**
     * 初始化应用主体
     * 此方法在应用程序构造函数的开头调用。它初始化几个重要的应用程序属性。
     * @param array $config 应用主体配置项
     * @throws InvalidConfigException 当没有配置 id 或者 basePath 时，会抛出异常
     */
    public function preInit(&$config)
    {
        if (!isset($config['id'])) {
            throw new InvalidConfigException('The "id" configuration for the Application is required.');
        }
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
        }

        if (isset($config['vendorPath'])) {
            $this->setVendorPath($config['vendorPath']);
            unset($config['vendorPath']);
        } else {
            $this->getVendorPath();
        }

        if (isset($config['runtimePath'])) {
            $this->setRuntimePath($config['runtimePath']);
            unset($config['runtimePath']);
        } else {
            $this->getRuntimePath();
        }

        if (isset($config['timeZone'])) {
            $this->setTimeZone($config['timeZone']);
        } elseif (!ini_get('date.timezone')) {
            $this->setTimeZone('UTC');
        }

        if (isset($config['container'])) {
            $this->setContainer($config['container']);
            unset($config['container']);
        }

        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }

    /**
     * 返回应用程序的核心组件
     * @return array
     */
    public function coreComponents()
    {
        return [
            'urlManager' => ['class' => 'yii\web\UrlManager'],
            'view' => ['class' => 'yii\web\View'],
        ];
    }

    /**
     * 返回请求组件
     * @return \yii\web\Request|\yii\console\Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * 返回视图对象
     * @return View|\yii\web\View 用于呈现各种视图文件的视图应用程序组件
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * 返回错误处理组件
     * @return  \yii\web\ErrorHandler|\yii\console\ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * 返回路由处理组件
     * @return \yii\web\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    /**
     * 将errorHandler组件注册为PHP错误处理程序。
     * @param array $config 应用主体的配置项
     */
    public function registerErrorHandler($config)
    {
        if (YII_ENABLE_ERROR_HANDLER) {
            if (!isset($config['components']['errorHandler']['class'])) {
                exit("Error: No errorHandler component is configured \n");
            }
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            $this->getErrorHandler()->register();
        }
    }

    /**
     * 设置应用程序的根目录和@app别名.
     * 此方法只能在构造函数的开头调用
     * @param string $path 应用程序的根目录
     */
    public function setBasePath($path)
    {
        parent::setBasePath($path);

        Yii::setAlias('@app', $this->getBasePath());
    }

    /**
     * @var string 第三方类库路径
     */
    private $_vendorPath;

    /**
     * 返回第三方类库路径
     * @return null|string
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }

        return $this->_vendorPath;
    }

    /**
     * 设置存储第三方文件路径
     * @param string $path 所配置的vendor的路径
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Yii::getAlias($path);
        Yii::setAlias('@vendor', $this->_vendorPath);
        Yii::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Yii::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }


    /**
     * @var string 系统运行时一些文件的存储路径
     */
    private $_runtimePath;

    /**
     * 设置存储运行时文件的路径
     * @param string $path 所配置的 runtime 的路径
     */
    public function setRuntimePath($path)
    {
        $this->_runtimePath = Yii::getAlias($path);
        Yii::setAlias('@runtime', $this->_runtimePath);
    }

    /**
     * 返回运行时文件所存储的路径
     * @return null|string
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
        }
        return $this->_runtimePath;
    }

    /**
     * 设置应用程序中所有时间函数的默认时区，
     * @param string $value 时区值
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    /**
     * 使用 $config 中的数据初始化 Yii::$container 对象。
     * @param array $config 键值对形式的配置项
     */
    public function setContainer($config)
    {
        Yii::configure(Yii::$container, $config);
    }
}