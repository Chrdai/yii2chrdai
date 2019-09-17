<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/5
 * Time: 18:52
 */

namespace yii\base;

use Yii;

class Controller extends Component implements ViewContextInterface
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
     * @var string 控制器的ID
     */
    public $id;

    /**
     * @var Module 该控制器所属的模块
     */
    public $module;

    /**
     * @var Controller 当前正在运行的控制器实例
     */
    public $controller;

    /**
     * @var Action 当前正在执行的方法。
     * 当[[应用主体]]调用[[run()]]来运行一个方法时，由[[run()]]进行调用。
     */
    public $action;

    /**
     * @var View 可用于呈现视图或视图文件的视图对象。
     */
    public $_view;

    /**
     * @var string 包含此控制器的视图文件的根目录。
     */
    public $_viewPath;

    /**
     * @var null|string|false 要应用于此控制器视图的布局的名称。
     * 此属性主要影响[[render()]]的行为。默认为null，这意味着实际的布局值应该继承自[[module]]的布局值。如果为false，则不应用布局。
     */
    public $layout;

    /**
     * @var string 默认方法名
     */
    public $defaultAction = 'index';

    /**
     * @param string $id 控制器的ID
     * @param Module $module 该控制器所属的模块
     * @param array $config 初始化对象的键值对数组
     */
    public function __construct($id, $module, array $config = [])
    {
        $this->id = $id;
        $this->module = $module;
        parent::__construct($config);
    }

    /**
     * 返回包含此控制器的视图文件的目录。
     * 默认实现返回在[[模块]]下名为controller [[id]]的目录[[viewPath]]目录。
     * @return string 包含此控制器的视图文件的目录。
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->module->getViewPath() . DIRECTORY_SEPARATOR . $this->id;
        }

        return $this->_viewPath;
    }

    /**
     * 此方法在执行此模块中的方法后立即调用。
     *
     * 该方法将触发[[EVENT_AFTER_ACTION]]事件。使用指定的方法和参数在此控制器中运行操作。
     * 如果方法为空，该方法将使用该方法的返回值[[defaultAction]]将用作操作返回值。
     * @param string $id 即将被执行的方法ID。
     * @param array $params 要传递给操作的参数(键值对)
     * @return mixed 方法的返回结果
     * @throws InvalidRouteException bug不能成功的创建方法。
     */
    public function runAction($id, $params = [])
    {
        $action = $this->createAction($id);
        if ($action === null) {
            throw new InvalidRouteException('Unable to resolve the request: ' . $this->getUniqueId() . '/' . $id);
        }
        //TODO Yii::debug('Route to run: ' . $action->getUniqueId(), __METHOD__);

        if (Yii::$app->requestedAction === null) {
            Yii::$app->requestedAction = $action;
        }

        $oldAction = $this->action;

        $modules = [];
        $runAction = true;

        //调用模块的 beforeAction()
        foreach ($this->getModules() as $module) {
            /* @var $module Module */
            if ($module->beforeAction($action)) {
                array_unshift($modules, $module);
            } else {
                $runAction = false;
                break;
            }
        }

        $result = null;

        if ($runAction && $this->beforeAction($action)) {
            //执行方法
            $result = $action->runWithParams($params);

            $result = $this->afterAction($action, $result);

            //调用模块的 afterAction()
            foreach ($modules as $module) {
                /* @var $module Module */
                $result = $module->afterAction($action, $result);
            }
        }

        if ($oldAction === null) {
            $this->action = $oldAction;
        }

        return $result;
    }

    /**
     * 返回此控制器的所有模块(包括祖先模块和子孙模块)。
     * 数组中的第一个模块是最外层的模块(即，应用程序实例)，最后一个是最里面的。
     * @return array
     */
    public function getModules()
    {
        $modules = [$this->module];
        $module = $this->module;
        while ($module->module !== null) {
            array_unshift($modules, $module->module);
            $module = $module->module;
        }

        return $modules;
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
     * InlineAction表示定义为控制器方法的action。基于给定的actionID创建操作。
     * 方法首先检查动作ID是否在[[actions()]]中声明。如果是这样,它将使用这里声明的配置来创建action对象。
     * 如果没有，它将寻找一个控制器方法，其名称的格式为“actionXyz”
     * 其中“Xyz”表示动作ID。如果找到，[[InlineAction]]表示该ID方法将被创建并返回。
     *
     * 控制器方法的名称可以通过[[actionMethod]]获得,由创建此操作的[[controller]]设置。
     *
     * @param string $id Action的ID
     * @return Action|null 新创建的action实例。如果ID没有解析为任何操作，则为Null。
     */
    public function createAction($id)
    {
        if ($id == '') {
            $id = $this->defaultAction;
        }
        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return Yii::createObject($actionMap[$id], [$id, $this]);
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }
    }

    /**
     * 为控制器声明外部操作。
     * 这个方法是为了声明控制器的外部操作而重写的。
     * 它应该返回一个数组，数组键是动作id，数组值是相应的动作类名或动作配置数组。例如
     *
     * ```php
     * return [
     *     'action1' => 'app\components\Action1',
     *     'action2' => [
     *         'class' => 'app\components\Action2',
     *         'property1' => 'value1',
     *         'property2' => 'value2',
     *     ],
     * ];
     * ```
     *
     * [[\Yii::createObject()]]稍后将使用这里提供的配置创建请求的操作
     */
    public function actions()
    {
        return [];
    }

    /**
     * 返回控制器的唯一ID。
     * @return string 以模块ID(如果有的话)为前缀的控制器ID。
     */
    public function getUniqueId()
    {
        return $this->module instanceof Application ? $this->id : $this->module->getUniqueId() . '/' . $this->id;
    }

    /**
     * 将参数绑定到操作。
     * 当它开始使用给定的参数运行时，[[Action]]调用该方法。
     * @param Action $action 要绑定参数的Action
     * @param array $params 要绑定到操作的参数。
     * @return array 操作可以使用的有效参数
     */
    public function bindActionParams($action, $params)
    {
        return [];
    }

    /**
     * 呈现视图并在可用时应用布局。
     *
     * 要呈现的视图可以用下列格式之一指定:
     * -[路径别名](指南:概念别名)(e.g. "@app/views/site/index");
     * -应用程式内的绝对路径(例如“//site/index”):视图名称以双斜杠开头。实际的视图文件将在应用程序的[[Application::viewPath|view path]]下查找。
     * -模块内的绝对路径(例如“/site/index”):视图名称以一个斜杠开始。实际的视图文件将在[[Module]]的[[Module::viewPath|view path]]下查找。
     * -相对路径(e.g. "index"):实际的视图文件将在[[viewPath]]下查找。
     *
     * 为决定应采用哪种布局，可进行以下两步:
     * 1. 第一步，确定布局名称和上下文模块:
     * -如果[[layout]]指定为字符串，则使用它作为布局名称，[[module]]作为上下文模块;
     * -如果[[layout]]为空，搜索该控制器的所有祖先模块，找到第一个模块的[[module::layout|layout]]不是空的。布局和相应的模块分别用作布局名称和上下文模块。
     * 如果没有找到这样的模块或相应的布局不是字符串，它将返回false，这意味着没有适用的布局。
     *
     * 2. 在第二步中，它根据前面找到的布局名称确定实际的布局文件和上下文模块。布局名称可以是:
     * -[路径别名](e.g. "@app/views/layouts/main");
     * -绝对路径(例如::布局名称以斜杠开始。实际的布局文件将是在应用程序的[[Application::layoutPath|layout path]]下查找;
     * -相对路径(例如:“main”):实际布局文件将在[[Module::layoutPath|layout path]]上下文模块下面查找
     *
     * 如果布局名称不包含文件扩展名，它将使用默认的' .php '。
     *
     * @param string $view 视图文件名
     * @param array $params 应该在视图中提供的参数(键值对)。这些参数在布局中不可用。
     * @return string 渲染结果
     * @throws InvalidArgumentException 如果视图文件或者布局文件没找到。
     */
    public function render($view, $params = [])
    {
        $content = $this->getView()->render($view, $params, $this);
        return $this->renderContent($content);
    }

    /**
     * 返回可用于呈现视图或视图文件的视图对象。
     * 这个视图对象实现实际的视图呈现将使用[[render()]、[[renderPartial()]和[[renderFile()]]方法.
     * 如果没有设置，它将默认为“view”应用程序组件。
     * @return View|\yii\web\View 可用于呈现视图或视图文件的视图对象
     */
    public function getView()
    {
        if ($this->_view === null) {
            $this->_view = Yii::$app->getView();
        }

        return $this->_view;
    }

    /**
     * 通过应用布局呈现静态字符串。
     * @param string $content 正在呈现的静态字符串
     * @return string 使用给定静态字符串作为' $content '变量的布局的呈现结果。如果布局被禁用，字符串将返回。
     */
    public function renderContent($content)
    {
        $layoutFile = $this->findLayoutFile($this->getView());
        if ($layoutFile !== false) {
            return $this->getView()->renderFile($layoutFile, ['content' => $content], $this);
        }

        return $content;
    }

    /**
     * 找到应用主体的布局文件
     * @param View $view 呈现布局文件的视图对象。
     * @return bool|string 布局文件路径，如果不需要布局，则为false。
     * @throws InvalidArgumentException 如果使用无效的路径别名指定布局。
     */
    public function findLayoutFile($view)
    {
        $module = $this->module;
        if (is_string($this->layout)) {
            $layout = $this->layout;
        } elseif ($this->layout === null) {
            while ($module !== null && $module->layout === null) {
                $module = $module->module;
            }

            if ($module !== null && is_string($module->layout)) {
                $layout = $module->layout;
            }
        }

        if (!isset($layout)) {
            return false;
        }

        if (strncmp($layout, '@', 1) === 0) {
            $file = Yii::getAlias($layout);
        } elseif (strncmp($layout, '/', 1) === 0) {
            $file = Yii::$app->getLayoutPath() . DIRECTORY_SEPARATOR . substr($layout, 1);
        } else {
            $file = $module->getLayoutPath() . DIRECTORY_SEPARATOR . $layout;
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }

        $path = $file . '.' . $view->defaultExtension;
        if ($view->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }

}