<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/12
 * Time: 15:39
 */

namespace yii\base;

use Yii;
use yii\helpers\FileHelper;

class View extends Component implements DynamicContentAwareInterface
{
    /**
     * @event Event 由[[beginPage()]]触发的事件。
     */
    const EVENT_BEGIN_PATE = 'begin_page';

    /**
     * @event Event 由[[endPage()]]触发的事件。
     */
    const EVENT_END_PAGE = 'end_page';

    /**
     * @event ViewEvent 在呈现视图文件之前由[[renderFile()]]触发的事件。
     */
    const EVENT_BEFORE_RENDER = 'beforeRender';

    /**
     * @event ViewEvent 在呈现视图文件之后由[[renderFile()]]触发的事件。
     */
    const EVENT_AFTER_RENDER = 'afterRender';

    /**
     * @var string 默认视图文件扩展名。如果文件名没有文件扩展名，这将被附加到视图文件名中。
     */
    public $defaultExtension = 'php';

    /**
     * @var Theme|array|string 主题对象或用于创建主题对象的配置。如果没有设置，则表示没有启用主题。
     */
    public $theme;

    /**
     * @var ViewContextInterface 调用[[renderFile()]]方法的context。
     */
    public $context;

    /**
     * @var array 是否指向一个可用呈现程序列表，该列表由其相应的受支持的文件扩展名索引。
     * 每个渲染器可以是一个视图渲染器对象，也可以是创建渲染器对象的配置。
     * 例如，下面的配置支持Smarty和Twig视图渲染器:继续渲染视图文件。
     *
     * ```php
     * [
     *     'tpl' => ['class' => 'yii\smarty\ViewRenderer'],
     *     'twig' => ['class' => 'yii\twig\ViewRenderer'],
     * ]
     * ```
     * 如果给定的视图文件没有呈现程序可用，则视图文件将被视为正常的PHP并通过[[renderPhpFile()]]呈现。
     */
    public $renderers;

    /**
     * @var array 当前呈现的视图文件。可能存在多个视图文件同时呈现，因为一个视图可能在另一个视图中呈现。
     */
    public $_viewFiles;


    public function render($view, $params = [], $context = null)
    {
        $viewFile = $this->findViewFile($view, $context);
        return $this->renderFile($viewFile, $params, $context);
    }

    /**
     * 根据给定的视图名称查找视图文件。
     * @param string $view 视图文件的视图名称或[path alias](guide:concept-aliases)。关于如何指定该参数，请参考[[render()]]
     * @param object $context 要分配给视图的context，以后可以通过[[context]]访问它。
     * 在视图中。如果context实现了[[ViewContextInterface]]，也可以使用它来定位对应于相对视图名称的视图文件。
     * @return string 视图文件路径。注意，该文件可能不存在。
     * @throws InvalidCallException 如果在没有活跃的context时，给出相对视图名确定相应的视图文件。
     */
    protected function findViewFile($view, $context = null)
    {
        if (strncmp($view, '@', 1) === 0) {
            // e.g. "@app/view/main"
            $file = Yii::getAlias($view);
        } elseif (strncmp($view, '//', 2) === 0) {
            // e.g. "//layouts/main"
            $file = Yii::$app->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
        } elseif (strncmp($view, '/', 1) === 0) {
            // e.g. "/site/index"
            if (Yii::$app->controller !== null) {
                $file = Yii::$app->controller->module->getViewPath() . DIRECTORY_SEPARATOR . ltrim($view, '/');
            } else {
                throw new InvalidCallException("Unable to locate view file for view '$view': no active controller.");
            }
        } elseif ($context instanceof ViewContextInterface) {
            $file = $context->getViewPath() . DIRECTORY_SEPARATOR . $view;
        } elseif (($currentViewFile = $this->getViewFile()) !== false) {
            $file = dirname($currentViewFile) . DIRECTORY_SEPARATOR . $view;
        } else {
            throw new InvalidCallException("Unable to resolve view file for view '$view': no active view context.");
        }

        if (pathinfo($file, PATHINFO_EXTENSION) !== '') {
            return $file;
        }

        $path = $file . '.' . $this->defaultExtension;
        if ($this->defaultExtension !== 'php' && !is_file($path)) {
            $path = $file . '.php';
        }

        return $path;
    }

    /**
     * 渲染一个视图文件
     *
     * 如果[[theme]]被启用(不是null)，它将尝试渲染视图文件的主题版本
     *
     * 该方法将调用[[FileHelper::localize()]]来本地化视图文件。
     *
     * 如果[[renderers|renderer]]被启用(不是null)，该方法将使用它来呈现视图文件。
     * 否则，它将简单地将视图文件包含为一个普通的PHP文件，捕获其输出并以字符串的形式返回。
     *
     * @param string $viewFile 视图文件。这可以是一个绝对文件路径，也可以是它的别名。
     * @param array $params 将被提取并在视图文件中可用的参数(键值对)。
     * @param object $context 视图用于呈现视图的context。如果为空,将使用现有的[[context]]。
     * @return string 渲染结果
     * @throws ViewNotFoundException 如果所渲染的文件不存在
     */
    public function renderFile($viewFile, $params = [], $context = null)
    {
        $viewFile = Yii::getAlias($viewFile);

        if ($this->theme !== null) {
            $viewFile = $this->theme->applyTo($viewFile);
        }
        if (is_file($viewFile)) {
            $viewFile = FileHelper::localize($viewFile);
        } else {
            throw new ViewNotFoundException("The view file does not exist: $viewFile");
        }

        $oldContext = $this->context;
        if ($context !== null) {
            $this->context = $context;
        }

        $output = '';
        $this->_viewFiles[] = $viewFile;

        if ($this->beforeRender($viewFile, $params)) {
            //TODO Yii::debug("Rendering view file: $viewFile", __METHOD__);
            $ext = pathinfo($viewFile, PATHINFO_EXTENSION);
            if (isset($this->renderers[$ext])) {
                if (is_array($this->renderers[$ext]) || is_string($this->renderers[$ext])) {
                    $this->renderers[$ext] = Yii::createObject($this->renderers[$ext]);
                }
                /* @var $renderer ViewRenderer*/
                $renderer = $this->renderers[$ext];
                $output = $renderer->render($this, $viewFile, $params);
            } else {
                $output = $this->renderPhpFile($viewFile, $params);
            }

            $this->afterRender($viewFile, $params, $output);
        }

        array_pop($this->_viewFiles);
        $this->context = $oldContext;

        return $output;
    }

    /**
     * 在[[renderFile()]]呈现视图文件之前调用此方法。
     * 默认实现将触发[[EVENT_BEFORE_RENDER]]事件。
     * 如果覆盖此方法，请确保首先调用父实现。
     * @param string $viewFile 要呈现的视图文件。
     * @param array $params 传递给[[render()]方法的参数数组。
     * @return bool 是否继续呈现视图文件。
     */
    public function beforeRender($viewFile, $params)
    {
        $event = new ViewEvent([
            'viewFile' => $viewFile,
            'params' =>$params,
        ]);

        $this->trigger(self::EVENT_BEFORE_RENDER, $event);

        return $event->isValid;
    }

    /**
     * 在[[renderFile()]]呈现视图文件后立即调用此方法。
     * 默认实现将触发[[EVENT_AFTER_RENDER]]事件。
     * 如果覆盖此方法，请确保首先调用父实现。
     * @param string $viewFile 要呈现的视图文件。
     * @param array $params 传递给[[render()]方法的参数数组。
     * @param string $output 视图文件的呈现结果。更新此参数将通过[[renderFile()]]返回。
     */
    public function afterRender($viewFile, $params, &$output)
    {
        //TODO afterRender
        $event = new ViewEvent([
            'viewFile' => $viewFile,
            'params' => $params,
            'output' => $output,
        ]);
        $this->trigger(self::EVENT_AFTER_RENDER, $event);
        $output = $event->output;
    }

    /**
     * 将视图文件呈现为PHP脚本。
     *
     * 该方法将视图文件视为PHP脚本，并包含该文件。
     * 它提取给定的参数并使它们在视图文件中可用。
     * 该方法捕获包含的视图文件的输出，并将其作为字符串返回。
     *
     * 该方法主要由视图渲染器或[[renderFile()]]调用。
     *
     * @param string $_file_ 视图文件
     * @param array $_params_ 将被提取并在视图文件中可用的参数(键值对)。
     * @return string 渲染结果
     * @throws \Exception
     * @throws \Throwable
     */
    public function renderPhpFile($_file_, $_params_ = [])
    {
        $_obInitialLevel_ = ob_get_level();
        ob_start();
        ob_implicit_flush(false);
        extract($_params_, EXTR_OVERWRITE);
        try {
            require $_file_;
            return ob_get_clean();
        } catch (\Exception $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw $e;
        } catch (\Throwable $e) {
            while (ob_get_level() > $_obInitialLevel_) {
                if (!@ob_end_clean()) {
                    ob_clean();
                }
            }
            throw new  $e;
        }
    }

    /**
     * 标记视图页的开头。
     */
    public function beginPage()
    {
        ob_start();
        ob_implicit_flush(false);

        $this->trigger(self::EVENT_BEGIN_PATE);
    }

    /**
     * 标记视图页的结束。
     */
    public function endPage()
    {
        $this->trigger(self::EVENT_END_PAGE);
        ob_end_flush();
    }

    /**
     * @return string|bool 当前呈现的视图文件。如果没有呈现视图文件，则为False。
     */
    public function getViewFile()
    {
        return end($this->_viewFiles);
    }

    /**
     * @return mixed 返回动态内容的placeholders。这个方法内部用于实现内容缓存特性。
     */
    public function getDynamicPlaceholders()
    {

    }

    /**
     * 为动态内容设置placeholders。这个方法在内部用于实现内容缓存特性。
     * @param array $placeholders
     */
    public function setDynamicPlaceholders($placeholders)
    {

    }

    /**
     * 为动态内容添加 placeholder。此方法在内部用于实现内容缓存特性。
     * @param string $name the placeholder name.
     * @param string $statements 用于生成动态内容的PHP语句
     * @return mixed
     */
    public function addDynamicPlaceholders($name, $statements)
    {

    }


}