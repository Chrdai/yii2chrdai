<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/4/4
 * Time: 17:48
 */

namespace core\web;


use core\base\BaseObject;

class Response extends BaseObject
{
    /**
     * @var null HeaderCollection
     */
    private $_header = null;

    private $_cookie = null;

    /**
     * @var bool 响应是否已发送，如果为true，则 send() 方法将什么也不执行，直接return.
     */
    public $isSent = false;

    /**
     * @var resource|array 即将被发送的流，可以使一个流句柄，也可以是一个流句柄数组
     */
    public $stream = null;

    /**
     * @var string http协议版本号，如果没有设置，默认使用$_SERVER['SERVER_PROTOCOL']的值
     */
    public $version = null;

    /**
     * @var string 响应的字符编码
     */
    public $charset = null;

    /**
     * @var int http状态码
     */
    private $_statusCode = 200;

    /**
     * @var string http状态码说明
     */
    public $statusText = 'OK';

    /**
     * @var array http状态码和状态码说明对应数组
     */
    public static $httpStatuses = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        118 => 'Connection timed out',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        210 => 'Content Different',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Reserved',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        310 => 'Too many Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range unsatisfiable',
        417 => 'Expectation failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable entity',
        423 => 'Locked',
        424 => 'Method failure',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        449 => 'Retry With',
        450 => 'Blocked by Windows Parental Controls',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway or Proxy Error',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        507 => 'Insufficient storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    /**
     * @purpose: 初始化设置 http协议版本号，响应的字符编码
     */
    public function init()
    {
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }

        if ($this->charset === null) {
            $this->charset = 'UTF-8';
        }
        parent::init();
    }

    /**
     * @return HeaderCollection|null http响应的状态码
     */
    public function getHeaders()
    {
        if ($this->_header == null){
            $this->_header = new HeaderCollection();
        }
        return $this->_header;
    }

    public function getCookies()
    {
        if ($this->_cookie == null) {
            $this->_cookie = new CookieCollection();
        }
        return $this->_cookie;
    }

    public function send()
    {
        if ($this->isSent) {
            return;
        }
        $this->prepare();
//        $this->sendHeader();
        $this->sendContent();
        $this->isSent = true;
    }

    public function prepare()
    {
        if ($this->stream !== null) {
            return;
        }
    }

    /**
     * @purpose: 发送http报头到客户端
     * @throws \Exception 如果http报头已发送，抛出该异常
     */
    public function sendHeader()
    {
        if (headers_sent($file, $line)) {
            throw new \Exception("Headers already sent in {$file} on line {$line}");
        }
        $headers = $this->getHeaders();
        foreach($headers as $name => $values) {
            //所有的http报头的名称都是首字母大写，且多个单词以 - 分隔
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            $replace = true;
            foreach($values as $value) {
                header("$name: $value", $replace);
                $replace = false; //强制使相同的头信息并存
            }
        }
        $statusCode = $this->getStatusCode();
        header("HTTP/{$this->version} {$statusCode} {$this->statusText}");
        $this->sendCookies();
    }

    public function sendContent()
    {
        if (empty($this->stream)) {
            return;
        }

        set_time_limit(0); //不限制脚本的最大执行时间，是为了发送大文件
        $chunkSize = 1024 * 8; // 如果文件比较大，循环读取文件，每次读取8M大小

        //如果$this->stream是一个数组，需要注意读取文件的开始位置和结束位置。
        if (is_array($this->stream)) {
            list($handle, $start, $end) = $this->stream;
            fseek($handle, $start);
            while (!feof($handle) && ($pos = ftell($handle) <= $end)) {
                if ($end - $pos < $chunkSize) {
                    $chunkSize = $end - $pos + 1;
                }
                echo fread($handle, $chunkSize);
                flush(); //因为此处是循环处理的，每次处理完毕后需要释放内存，否则可能会超过php的最大内存限制。
            }
            fclose($handle);
        } else {
            //如果$this->stream是一个字符串，直接读取，每次读取8M
            while (!feof($this->stream)) {
                echo fread($this->stream, $chunkSize);
                flush();
            }
            fclose($this->stream);
        }
    }

    /**
     * @purpose: 将cookie发送到客户端
     */
    public function sendCookies()
    {
        $cookieValidationKey = '4wVg3lxHteCxf3xR-F7NisLJ0wMLjvhM';
        $cookies = $this->getCookies();
        foreach ($cookies as $cookie) {
            setcookie($cookie->name, $cookie->value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }
    /**
     * @purpose: 设置响应相关http状态码
     * @param int $code http状态码，如果为 null,默认为 200
     * @param null $text 状态码说明，如果为 null，使用默认状态码说明，比如 404 的默认说明为 Not Found
     * @return $this
     * @throws \Exception 如果状态码无效，抛出异常，界定范围:(100 <= $code < 600)
     */
    public function setStatusCode($code, $text = null)
    {
        if ($code === null) {
            $code = 200;
        }
        $this->_statusCode = (int) $code;
        if ($this->getIsInvalid($code)) {
            throw new \Exception("无效的http状态码：{$code}");
        }
        if ($text === null) {
            $this->statusText = isset(self::$httpStatuses[$code]) ? self::$httpStatuses[$code] : 200;
        }  else {
            $this->statusText = $text;
        }
        return $this;
    }

    /**
     * @purpose: 判断http状态码是否有效
     * @param int $code 状态码
     * @return bool
     */
    public function getIsInvalid($code)
    {
        return $this->getStatusCode() < 100 || $this->getStatusCode() >= 600;
    }

    /**
     * @return int http响应所返回的状态码
     */
    public function getStatusCode()
    {
        return $this->_statusCode;
    }

    /**
     * @purpose: 向浏览器发送一个文件
     * 注意：该方法只是为发送文件做准备，直到控制器操作返回后执行了send()方法才会正在发送文件到浏览器
     * 使用示例：
     *
     * ```php
     * public function actionExport()
     * {
            return \Yii::$app->response->sendFile($filePath, $attachementName);
     * }
     * ```
     * @param string $filePath 所发送文件的全路径
     * @param sting null $attachment 展示给用户看的文件名，如果为 null, 默认使用 $filePath的值
     * @param array $options 选项值支持下面几个值：
     * - `mimeType`: 文件的 MIME 类型，如果没有设置该值，将自动依据$filePath来获取
     * - `inline`: 是否直接在浏览器窗口打开文件，默认为false, 会打开一个下载附件对话框
     * @return $this
     */
    public function sendFile($filePath, $attachment = null, $options = [])
    {
        if(!isset($options['mimeType'])){
            $options['mimeType'] = '';
        }
        if($attachment == null){
            $attachment = basename($filePath);
        }
        $handle = fopen($filePath, 'rb');
        $this->sendStreamAsFile($handle, $attachment, $options);

        return $this;
    }

    /**
     * @purpose: 将指定的流作为文件发送到浏览器
     * 注意：该方法只是为发送文件做准备，直到控制器操作返回后执行了send()方法才会正在发送文件到浏览器
     * @param resource $handle 待发送文件流的句柄
     * @param string $attachmentName 展示给用户看的文件名
     * @param array $options 选项值支持下面几个值：
     * - `mimeType`: 文件的 MIME 类型，如果没有设置该值，将自动依据$filePath来获取
     * - `inline`: 是否直接在浏览器窗口打开文件，默认为false, 会打开一个下载附件对话框
     * - `fileSize` : 当文件大小可知且内容又不可见的情况下将会用到该参数，默认是使用ftell()自动读取文件大小
     * @return $this
     * @throws \Exception
     */
    public function sendStreamAsFile($handle, $attachmentName, $options)
    {
        $headers = $this->getHeaders();
        if (isset($options['fileSize'])) {
            $fileSize = $options['fileSize'];
        } else {
            //将文件指针移动到文件末尾 SEEK_END + 0
            fseek($handle, 0, SEEK_END);
            //此时文件大小就等于指针的偏移量
            $fileSize = ftell($handle);
        }

        $range = $this->getHttpRange($fileSize);
        if ($range === false) {
            $headers->set('Content-Range', "bytes */$fileSize");
            throw new \Exception('获取http文件范围失败');
        }

        list($start, $end) = $range;
        if ($start <> 0 || $end <> $fileSize -1 ) {
            $this->setStatusCode(206);
            $headers->set('Content-Range', "bytes $start-$end/$fileSize");
        } else {
            $this->setStatusCode(200);
        }
        $mimeType = isset($options['mimeType']) ? $options['mimeType'] : 'application/octet-stream';
        $this->setDownloadHeaders($attachmentName, $mimeType, !empty($options['inline']) , $end - $start + 1);

        $this->stream = [$handle, $start, $end];

        return $this;
    }

    /**
     * @purpose: 确定请求中给定的http范围
     * @param int $fileSize 文件的大小，将会用来验证和界定“http range”的范围
     * @return array|boolean 如果http range获取错误，返回false，否则返回一个包含start和end的数组
     */
    public function getHttpRange($fileSize)
    {
        $rangeHeader = $this->getHeaders()->get('Range', '-');
        if ($rangeHeader === '-') {
            return [0, $fileSize-1];
        }
        if (!preg_match('/^bytes:(\d*)-(\d*)$/', $rangeHeader, $matches)) {
            return false;
        }
        if ($matches[1] === '') {               // -100 ：最后100个字节
            $start = $fileSize - $matches[2];
            $end = $fileSize - 1;
        } elseif ($matches[2] !== '') {         //100-200 ：从第100字节到第200字节
            $start = $matches[1];
            $end = $matches[2];
            if ($end >= $fileSize) {
                $end = $fileSize - 1;
            }
        } else {                                //100- ：从第100到结尾
            $start = $matches[1];
            $end = $fileSize - 1;
        }
        if ($start < 0 || $start > $end) {
            return false;
        }
        return [$start, $end];
    }

    /**
     * @purpose: 设置下载文件的默认http报头
     * @param string $attachementName 展示给用户看的文件名
     * @param string $mimeType 文件的MIME类型，如果为 null，响应的http报头将不会设置 Content-Type
     * @param bool $inline 默认为false,将会打开一个下载附件的对话框，如果设为true,将会在当前浏览器页面打开所下载的文件内容。
     * @param int $contentLength 所下载文件的大小，单位为字节，如果为 null，响应的http报头将不会设置 Content-Length
     * @return $this
     */
    public function setDownloadHeaders($attachementName, $mimeType, $inline = false , $contentLength)
    {
        $headers = $this->getHeaders();

        $disposition = $inline ? 'inline' : 'attachment';
        $headers->setDefault('Pragma','public') //响应可被任何缓存区缓存
            //支持断点传输，单位是bytes
            ->setDefault('Accept-Ranges','bytes')
            //缓存已过期
            ->setDefault('Expires', 0)
            //使得客户端再次浏览当前页时必须发送相关 HTTP 头信息到服务器进行验证，然后才决定是否加载客户端本地 cache
            ->setDefault('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
            //类型为附件， 附件名为 ：xxx
            ->setDefault('Content-Disposition', $this->getDispositionHeaderValue($disposition, $attachementName));

        if($mimeType){
            //下载文件的类型
            $headers->setDefault('Content-Type', $mimeType);
        }
        if($contentLength){
            //文件大小
            $headers->setDefault('Content-Length', $contentLength);
        }

        return $this;
    }

    public function getDispositionHeaderValue($disposition, $attachmentName)
    {
        return $disposition . ';filename=' . $attachmentName;
    }
}