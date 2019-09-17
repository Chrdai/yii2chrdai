<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/9
 * Time: 16:28
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * @property HeaderCollection $headers The header collection. This property is read-only.
 * Class Request
 * @package yii\web
 */
class Request extends Component
{
    /**
     * @var string 用于指示请求是PUT、PATCH还是DELETE的POST参数的名称，请求通过POST隧道。默认为“_method”。
     */
    public $_methodParam = '_method';

    /**
     * @var array 可信安全相关报头的配置。
     * 数组键是一个IPv4或IPv6 IP地址在CIDR符号匹配客户端。
     * 数组值是要信任的头的列表。这些将与之匹配
     * [[secureHeaders]]来确定哪些头文件允许由指定的主机发送。
     *
     * 例如，要信任[[secureHeaders]]中列出的所有IP地址头
     * 在“192.168.0.0-192.168.0.254”范围内写下以下内容:
     *
     * ```php
     * [
     *     '192.168.0.0/24',
     * ]
     * ```
     *
     * 要仅信任“10.0.0.1”中的“x-forward-for”头文件，请使用:
     *
     * ```
     * [
     *     '10.0.0.1' => ['X-Forwarded-For']
     * ]
     * ```
     *
     * 默认值是信任所有主机的所有头，除了[[secureHeaders]]中列出的头之外。
     * 按顺序尝试匹配，当IP匹配时停止搜索。
     *
     * >信息:使用[[IpValidator]]执行匹配。
     * 看到[[IpValidator:::: setRanges () | IpValidator:: setRanges ()))
     * 和[[IpValidator::networks]]用于高级匹配。
     *
     * @see $secureHeaders
     * @since 2.0.13
     */
    public $trustedHosts = [];

    /**
     * @var array 默认情况下受受信任主机配置约束的标题列表。
     * 除非在[[trustedHosts]]中显式允许，否则将过滤这些头。
     * 标题名称的匹配不区分大小写。
     * @see $trustedHosts
     * @since 2.0.13
     */
    public $secureHeaders = [
        //Common:
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',

        //Microsoft:
        'Front-End-Https:',
        'X-Rewrite-Url',
    ];

    /**
     * @var array 检查是否通过HTTPS建立连接的http报头。
     * 数组键是报头名称，数组值是表示安全连接的报头值列表。
     * 头名和值的匹配不区分大小写。
     * 不建议在这里放置不安全的头文件。
     * @see $trustedHosts
     * @see $secureHeaders
     * @since 2.0.13
     */
    public $secureProtocolHeaders = [
        'X-Forwarded-Proto' => ['https'], //Common
        'Front-End-Https' => ['on'],  //Microsoft
    ];

    /**
     * @var HeaderCollection 请求头集合类.
     */
    private $_headers;

    /**
     * 将当前请求保存到路由和相关参数中。
     * @return array 第一个元素是路由，第二个是相关参数。
     * @throws NotFoundHttpException 如果请求没有被成功解析
     */
    public function resolve()
    {
        $result = Yii::$app->getUrlManager()->parseRequest($this);
        if ($result !== false) {
            list($route, $params) = $result;
            if ($this->_queryParams === null) {
                $_GET = $params + $_GET; // preserve numeric keys
            } else {
                $this->_queryParams = $params + $this->_queryParams;
            }

            return [$route, $this->getQueryParams()];
        }

        throw new NotFoundHttpException('yii', 'Page not found.');
    }

    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection();
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
                foreach ($headers as $name => $value) {
                    $this->_headers->add($name, $value);
                }
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
                foreach ($headers as $name => $value) {
                    $this->_headers->add($name, $value);
                }
            } else {
                foreach ($_SERVER as $name => $value) {
                    if (strcasecmp($name, 'HTTP_', 5) === 0) {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $this->_headers->add($name, $value);
                    }
                }
            }

            //$this->filterHeaders($this->_headers);
        }

        return $this->_headers;
    }

    /**
     * @return string 返回当前请求的方法，如（ GET, POST, HEAD, PUT, PATCH, DELETE）
     * 返回值会被转为大写。
     */
    public function getMethod()
    {
        if (isset($_POST[$this->_methodParam])) {
            return strtoupper($_POST[$this->_methodParam]);
        }

        if ($this->headers->has('X-Http-Method-Override')) {
            return strtoupper($this->headers->get('X-Http-Method-Override'));
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            return $_SERVER['REQUEST_METHOD'];
        }

        return 'GET';
    }

    private $_url;

    /**
     * 返回当前请求的相对 url。
     * 这指的是URL中位于[[hostInfo]]部分之后的部分。即不包括协议域名和端口
     * 如果有的话，它包括[[queryString]]部分。即包括请求的参数
     *
     */
    public function getUrl()
    {
        if ($this->_url === null) {
            $this->_url = $this->resolveRequestUri();
        }

        return $this->_url;
    }

    /**
     * 设置当前请求的相对URL，
     * 该url是url地址后【hostInfo】后面的部分。’
     * 注：该url地址必须是 URL-decoded
     * @param string $value 待设置的url值。
     */
    public function setUrl($value)
    {
        $this->_url = $value;
    }

    /**
     * 解析当前请求URL的请求URI部分。
     * 这是指[[hostInfo]]部分后面的部分。它包括[[queryString]]部分(如果有的话)。
     * 该方法的实现在Zend框架中引用了Zend_Controller_Request_Http。
     * @return mixed|string 请求URL的请求URI部分。
     * @throws InvalidConfigException 如果由于不正常的服务器配置而无法确定请求URI
     */
    protected function resolveRequestUri()
    {
        if ($this->headers->has('X-Rewrite-Url')) {
            $requestUri = $this->headers->get('X-Rewrite-Url');
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $requestUri = $_SERVER['REQUEST_URI'];
            if ($requestUri !== '' && $requestUri !== '/') {
                $requestUri = preg_replace('/^(http|https):\/\/[^\/]+/i', '', $requestUri);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $requestUri = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $requestUri .= '?' . $_SERVER['QUERY_STRING'];
            }
        } else {
            throw new InvalidConfigException('Unable to determine the request URI.');
        }

        return $requestUri;
    }


    private $_pathInfo;

    /**
     * 返回当前请求url的path info部分。
     * pathInfo 指的是在入口脚本之后和问号(查询字符串)之前的部分。前后的斜杆都不要。
     * 注：返回的path info是 URL-decoded 之后的路径。
     * @return mixed
     */
    public function getPathInfo()
    {
        if ($this->_pathInfo === null) {
            $this->_pathInfo = $this->resolvePathInfo();
        }

        return $this->_pathInfo;
    }

    /**
     * 解析当前请求URL的 path info 部分。
     * path info 是指在输入脚本之后和问号(查询字符串)之前的部分。
     * 开始斜杠都被删除(结束斜杠将保留)。
     * @return bool|mixed|string
     * @throws InvalidConfigException
     */
    protected function resolvePathInfo()
    {
        $pathInfo = $this->getUrl();

        if (($pos = strpos($pathInfo, '?')) !== false) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = urldecode($pathInfo);

        // try to encode in UTF8 if not so
        // http://w3.org/International/questions/qa-forms-utf-8.html
        if (!preg_match('%^(?:
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            | \xE0[\xA0-\xBF][\x80-\xBF]         # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            | \xED[\x80-\x9F][\x80-\xBF]         # excluding surrogates
            | \xF0[\x90-\xBF][\x80-\xBF]{2}      # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            | \xF4[\x80-\x8F][\x80-\xBF]{2}      # plane 16
            )*$%xs', $pathInfo)
        ) {
            $pathInfo = utf8_encode($pathInfo);
        }

        $scriptUrl = $this->getScriptUrl();
        $baseUrl = $this->getBaseUrl();

        if (strpos($pathInfo, $scriptUrl) === 0) {
            $pathInfo = substr($pathInfo, strlen($scriptUrl));
        } elseif (strpos($pathInfo, $baseUrl)) {
            $pathInfo = substr($pathInfo, strlen($baseUrl));
        } elseif (isset($_SERVER['PHP_SELF']) && strpos($pathInfo, $_SERVER['PHP_SELF']) === 0) {
            $pathInfo = substr($_SERVER['PHP_SELF'], strlen($scriptUrl));
        } else {
            throw new InvalidConfigException('Unable to determine the path info of the current request.');
        }

        if (substr($pathInfo, 0, 1) === '/') {
            $pathInfo = substr($pathInfo, 1);
        }

        return $pathInfo;
    }

    /**
     * @var string 应用程序的相对路径
     */
    private $_baseUrl;

    /**
     * @var string 返回应用程序的相对路径。
     * 这个和 [scriptUrl]很像，只是不包含入口文件名。
     * @return string
     */
    public function getBaseUrl()
    {
        if ($this->_baseUrl === null) {
            $this->_baseUrl = rtrim(dirname($this->getScriptUrl()), '\\/');
        }

        return $this->_baseUrl;
    }

    /**
     * @var string 入口脚本的相对路径
     */
    private $_scriptUrl;

    /**
     * 返回入口脚本的相对路径
     * @return mixed|string
     * @throws InvalidConfigException
     */
    public function getScriptUrl()
    {
        if ($this->_scriptUrl === null) {
            $scriptFile = $this->getScriptFile();
            $scriptName = basename($scriptFile);
            if (isset($_SERVER['SCRIPT_NAME']) && basename($_SERVER['SCRIPT_NAME']) === $scriptName) {
                //包含当前脚本的路径，这在页面需要指向自己时非常有用
                $this->_scriptUrl = $_SERVER['SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && basename($_SERVER['PHP_SELF']) === $scriptName) {
                //当前执行脚本的文件名
                $this->_scriptUrl = $_SERVER['PHP_SELF'];
            } elseif (isset($_SERVER['ORIG_SCRIPT_NAME']) && basename($_SERVER['ORIG_SCRIPT_NAME']) === $scriptName) {
                $this->_scriptUrl = $_SERVER['ORIG_SCRIPT_NAME'];
            } elseif (isset($_SERVER['PHP_SELF']) && ($pos = strpos($_SERVER['PHP_SELF'], '/' . $scriptName)) !== false) {
                $this->_scriptUrl = substr($_SERVER['SCRIPT_NAME'], 0, $pos) . '/' . $scriptName;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT']) && strpos($scriptFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
                $this->_scriptUrl = str_replace([$_SERVER['DOCUMENT_ROOT'], '\\'], ['', '/'], $scriptFile);
            } else {
                throw new InvalidConfigException('Unable to determine the entry script URL.');
            }
        }

        return $this->_scriptUrl;
    }

    /**
     * @var string 入口脚本的路径
     */
    private $_scirptFile;

    /**
     * 返回入口脚本的路径。
     * 默认情况下，就是返回 $_SERVER['SCRIPT_FILENAME']的值。
     * @return mixed
     * @throws InvalidConfigException
     */
    public function getScriptFile()
    {
        if (isset($this->_scirptFile)) {
            return $this->_scirptFile;
        }

        //当前执行脚本的绝对路径
        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            return $_SERVER['SCRIPT_FILENAME'];
        }

        throw new InvalidConfigException('Unable to determine the entry script file path.');
    }

    private $_queryParams;

    /**
     * @return mixed 返回[[queryString]]中给定的请求参数。
     * 如果没有显式设置参数，该方法将返回' $_GET '的内容。
     * @see setQueryParams()
     */
    public function getQueryParams()
    {
        if ($this->_queryParams === null) {
            return $_GET;
        }

        return $this->_queryParams;
    }

    /**
     * 设置request [[queryString]]参数。
     * @param array $values 请求查询参数(名称-值对)
     */
    public function setQueryParams($values)
    {
        $this->_queryParams = $values;
    }

    /**
     * 返回指定的GET参数值。
     * 如果GET参数不存在，则返回传递给该方法的第二个参数。即默认值
     * @param string $name GET参数名。
     * @param mixed $defaultValue 如果$_GET[$name]存在，返回的默认值
     * @return mixed GET参数值
     * @see getBodyParam()
     */
    public function getQueryParam($name, $defaultValue = null)
    {
        $params = $this->getQueryParams();
        return isset($params[$name]) ? $params[$name] : $defaultValue;
    }

    private $_hostInfo;
    private $_hostName;

    /**
     * 返回当前请求URL的主机部分。
     *
     * 返回的URL没有结尾斜杠。
     *
     * 默认情况下，此值基于用户请求信息。该方法将返回“$_SERVER['HTTP_HOST']”(如果可用)和“$_SERVER['SERVER_NAME')”(如果不可用)的值。
     * 可以参看[PHP文档](http://php.net/manual/en/reserve.variables.server.php)
     * 有关这些变量的更多信息。
     *
     * 可以通过设置[[setHostInfo()|hostInfo]]属性显式地指定它。
     *
     * >注意:此信息可能不依赖于服务器配置
     * >可靠，[可能被发送HTTP请求的用户伪造](https://www.acunetix.com/vulnerabilities/web/host-header-attack)。
     * >，如果将web服务器配置为独立于' Host '报头，此值不可靠。在这种情况下，你也应该这样做
     * >修复您的web服务器配置，或者通过设置[[setHostInfo()|hostInfo]]属性显式地设置值。
     * >如果您没有访问服务器配置，您可以设置[[\yii\filters\HostControl]] filter at
     * >应用程序级别，以防止此类攻击。
     * @return null|string
     */
    public function getHostInfo()
    {
        if ($this->_hostInfo === null) {
            $secure = $this->getIsSecureConnection();
            $http = $secure ? 'https' : 'http';

            if ($this->headers->has('X-Forwarded-Host')) {
                $this->_hostInfo = $http . '://' . $this->headers->get('X-Forwarded-Host');
            } elseif ($this->headers->has('Host')) {
                $this->_hostInfo = $http . '://' . $this->headers->get('Host');
            } elseif (isset($_SERVER['SERVER_NAME'])) {
                $this->_hostInfo = $http . '://' . $_SERVER['SERVER_NAME'];
                $port = $secure ? $this->getSecurePort() : $this->getPort();
                if (($port !== 80 && !$secure) || ($port !== 443 && $secure)) {
                    $this->_hostInfo .= ':' . $port;
                }
            }
        }

        return $this->_hostInfo;
    }

    /**
     * @return bool 如果请求是通过安全通道(https)发送的，则返回 true, 否则返回false
     */
    protected function getIsSecureConnection()
    {
        if (isset($_SERVER['HTTPS']) && strcasecmp($_SERVER['HTTPS'], 'on') === 0 || $_SERVER['HTTPS'] == 1) {
            return true;
        }

        foreach ($this->secureProtocolHeaders as $header => $values) {
            if (($headerValue = $this->headers->get($header, null)) !== null) {
                foreach ($values as $value) {
                    if (strcasecmp($value, $headerValue) === 0) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private $_securePort;

    /**
     * @return int|null 返回使用 https 协议的端口号，默认为 443。
     */
    public function getSecurePort()
    {
        if ($this->getSecurePort() === null) {
            $serverPort = $this->getServerPort();
            $this->_securePort = $this->getIsSecureConnection() && $serverPort !== null ? $serverPort : 443;
        }

        return $this->_securePort;
    }

    /**
     * @return int|null 返回服务器的端口号
     */
    public function getServerPort()
    {
        return isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : null;
    }

    private $_port;

    /**
     * @return int|null 返回使用 http 协议的端口号，默认为 80。
     */
    public function getPort()
    {
        if ($this->_port === null) {
            $serverPort = $this->getServerPort();
            $this->_port = $this->getIsSecureConnection() && $serverPort !== null ? $serverPort : 80;
        }

        return $this->_port;
    }
}