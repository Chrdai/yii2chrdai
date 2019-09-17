<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/8
 * Time: 18:33
 */

namespace yii\base;

use Yii;

abstract class ErrorHandler extends Component
{
    /**
     * @var bool 是否在错误显示之前丢弃任何现有页面输出。默认为 true。
     */
    public $discardExistingOutput = true;

    /**
     * @var int 保留内存的大小。
     * 内存的一部分是预先分配的，这样当内存不足问题发生时，错误处理程序就能够在这个保留内存的帮助下处理错误。
     * 如果将此值设置为0，则不会保留任何内存。默认为256 kb。
     */
    public $memeoryReserveSize = 262144;

    /**
     * @var string 用于为致命错误处理程序保留内存。
     */
    private $_memoryReserve;

    /**
     * @var \Exception|null 当前正在处理的异常。
     */
    public $excetion;

    /**
     * @var \Exception 回存HHVM的输出异常
     */
    public $_hhvmException;

    /**
     * 注册此错误处理程序。
     */
    public function register()
    {
        ini_set('display_errors', false);
        set_exception_handler([$this, 'handleException']);
        if (defined('HHVM_VERSION')) {
            set_error_handler([$this, 'handleHhvmError']);
        } else {
            set_error_handler([$this, 'handleError']);
        }

        if ($this->memeoryReserveSize > 0) {
            $this->_memoryReserve = str_repeat('x', $this->memeoryReserveSize);
        }

        register_shutdown_function([$this, 'handleFatalError']);
    }

    /**
     * 恢复PHP错误和异常处理程序
     */
    public function unregister()
    {
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * 处理未捕获的 PHP 异常。
     * 此方法实现为 PHP 异常处理程序
     * @param \Exception $exception 未捕获的PHP异常
     */
    public function handleException($exception)
    {
        //正常退出
        if ($exception instanceof ExitException) {
            return;
        }

        //将HTTP状态代码设置为500，以防错误处理以某种方式失败并发送报头
        //HTTP异常将覆盖renderException()中的这个值
        $this->excetion = $exception;
        //在处理异常时禁用错误捕获以避免递归错误
        $this->unregister();

        if (PHP_SAPI !== 'cli') {
            http_response_code(500);
        }

        try {
            $this->logException($exception);
            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);
            if(YII_ENV_TEST) {
                //TODO \Yii::getLogger()->flush(true);
                if (defined('HHVM_VERSION')) {
                    flush();
                }
                exit(1);
            }
        } catch (\Exception $e) {
            //在显示异常时可以抛出另一个异常
            $this->handleFallbackExceptionMessage($e, $exception);
        } catch (\Throwable $e) {
            //PHP 7中引入的对Throwable的附加检查
            $this->handleFallbackExceptionMessage($e, $exception);
        }
    }

    /**
     * 处理HHVM执行错误，如警告和通知.
     * 此方法用作HHVM错误处理程序。它将存储用于致命错误处理程序的异常
     * @param int $code 错误的级别。
     * @param string $message 错误消息
     * @param string $file 错误引发的文件名
     * @param int $line 错误行号
     * @param mixed $context
     * @param mixed $backtrace 错误跟踪
     * @return bool 是否继续正常的错误处理程序
     */
    public function handleHhvmError($code, $message, $file, $line, $context, $backtrace)
    {
        if ($this->handleError($code, $message, $file, $line)) {
            return true;
        }

        if (E_ERROR && $code) {
            $exception = new \ErrorException($message, $code, $code, $file, $line);
            $ref = new \ReflectionProperty('\Exception', 'trace');
            $ref->setAccessible(true);
            $ref->setValue($exception, $backtrace);
            $this->_hhvmException = $exception;
        }
    }

    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() && $code) {
            if (!class_exists('yii\\base\\ErrorException', false)) {
                //require_once __DIR__ . '/ErrorException.php';
            }
        }
    }

    /**
     * 显示异常。
     * @param \Exception $exception 待呈现的异常
     * @return mixed
     */
    abstract protected function renderException($exception);

    /**
     * 在处理[[handleException()]中的异常过程中抛出的异常。
     * @param \Exception|\Throwable $exception 在主异常处理期间引发的异常。
     * @param \Exception $previousException 在[[handleException()]]中处理的主异常。
     */
    public function handleFallbackExceptionMessage($exception, $previousException)
    {
        $msg = "An Error occurred while handling another error:\n";
        $msg .= (string)$exception;
        $msg .= "\nPrevious exception:\n";
        $msg .= (string)$previousException;

        if (YII_DEBUG) {
            if (PHP_SAPI === 'cli') {
                echo $msg . "\n";
            } else {
                echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES, Yii::$app->charset) . '</pre>';
            }
        } else {
            echo "an internal error occurred.";
        }
        //TODO VarDumper::export
        $msg .= "\n\$_SERVER = " . var_export($_SERVER);
        error_log($msg);
        if (defined('HHVM_VERSION')) {
            flush();
        }
        exit(1);
    }

    /**
     * 将给定的异常记录日志。
     * @param \yii\web\HttpException $exception 待记录的异常
     */
    public function logException($exception) {
        $category = get_class($exception);
        if ($exception instanceof \HttpException) {
            $category = 'yii\\web\\HttpException:' . $exception->statusCode;
        } elseif ($exception instanceof \ErrorException) {
            //获取异常的严重程度
            $category .= ':' . $exception->getSeverity();
        }

        //TODO Yii::error($exception, $category);
    }

    /**
     * 在调用此方法之前删除所有回显的输出。
     */
    public function clearOutput()
    {
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }
}