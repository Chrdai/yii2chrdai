<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/12
 * Time: 10:02
 */

namespace yii\web;


use yii\base\BaseObject;

class UrlRule extends BaseObject implements UrlRuleInterface
{
    /**
     * 用于set [mode] 的值，仅仅只解析url
     */
    const PARSING_ONLY = 1;

    /**
     * 用于set [mode] 的值，仅仅只创建url
     */
    const CRETEING_ONLY = 2;

    /**
     * 在规则初始化时，匹配参数名称的[[pattern]]将被替换为[[placeholders]]。
     * @var string 用于解析和创建URL的路径信息部分的正则
     * @see host
     * @see placeholders。
     */
    public $pattern;

    /**
     * @var string 用于解析和创建URL的主机信息部分的正则 (e.g. `http://example.com`).
     * @see pattern
     */
    public $host;

    /**
     * @var string 控制器动作的路由
     */
    public $route;

    /**
     * @var array 此规则提供的默认GET参数(name =>value)。
     *当此规则用于解析传入的请求时，该属性中声明的值将被注入$_GET。
     */
    public $defaults = [];

    /**
     * @var array 用于匹配参数名称的占位符列表。在[[parseRequest()]]， [[createUrl()]]中使用。
     * 在规则初始化时，[[pattern]]参数名称将被占位符替换。
     * 这个数组包含原始参数名称与其占位符之间的关系。
     * 数组键是占位符，值是原始名称。
     *
     * @see parseRequest()
     * @see createUrl()
     * @since 2.0.7
     */
    protected $placeholders = [];

    /**
     * @var string 该规则的url后缀名。
     * 如 .html ,代表请求的是一个静态的html文件。
     * 如果没有设置，将会使用 [[UrlManager::suffix]] 来替代。
     */
    public $suffix;

    /**
     * @var string|array 本次请求的路由规则需要匹配的HTTP请求方法，（ 如：GET, POST, DELETE）。
     * 如果是数组，则需要匹配数组中所有的HTTP请求方法。
     * 如果该值未设置，本次请求的规则需要匹配所有的HTTP请求方法。
     * 注：该属性仅仅只用于解析url时使用，如果是创建url，则忽略该属性。
     */
    public $verb;

    /**
     * @var int 一个代表是“仅仅只创建url”还是“仅仅只解析url”的值。
     * 如果未设置或者等于0，意味着既可以创建url也可以解析url。其值如下：
     * [[PARSING_ONLY]] : 仅仅只解析url。
     * [[CREATING_ONLY]] : 仅仅只创建url。
     */
    public $mode;

    /**
     * @var UrlNormalizer|array|false|null 此规则使用的[[UrlNormalizer]]的配置。
     * 如果为“null”，将使用[[UrlManager::normalizer]];如果为“false”，将跳过标准化对于这条规则。
     */
    public $normalizer;

    /**
     * @var array 用于匹配路由部分的正则表达式。这用于生成URL
     */
    public $_routeRule = [];

    /**
     * @var array 用于匹配参数的正则表达式列表。这用于生成URL。
     */
    public $_paramRules = [];

    /**
     * @var array 路由中使用的参数列表。
     */
    public $_routeParams = [];

    /**
     * 解析给定的请求并返回相应的路由和参数。
     * @param UrlManager $manager url管理器
     * @param Request $request 请求组件
     * @return array|bool 解析结果，路由和参数作为数组返回。如果是“false”，则表示此规则不能用于解析此路径信息。
     */
    public function parseRequest($manager, $request)
    {
        if ($this->mode = self::CRETEING_ONLY) {
            return false;
        }

        if (!empty($this->verb) && !in_array($request->getMethod(), $this->verb ,true)) {
            return false;
        }

        $suffix = (string)($this->suffix === null ? $manager->suffix : $this->suffix);

        $pathInfo = $request->getPathInfo();
        $normalized = false;
        if ($this->hasNormalizer($manager)) {
            $pathInfo = $this->getNormalizer($manager)->normalizePathInfo($pathInfo, $suffix, $normalized);
        }
        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($suffix);
            if (substr_compare($pathInfo, $suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    // suffix alone is not allowed
                    return false;
                }
            } else {
                return false;
            }
        }

        if ($this->host !== null) {
            $pathInfo = strtolower($request->getHostInfo()) . ($pathInfo === '' ? '' : '/' . $pathInfo);
        }

        if (!preg_match($this->pattern, $pathInfo, $matchs)) {
            return false;
        }

        $matchs = $this->substitutePlaceholderNames($matchs);

        foreach ($this->defaults as $name => $value) {
            if (!isset($matchs[$name]) || $matchs[$name] === '') {
                $matchs[$name] = $value;
            }
        }
        $params = $this->defaults;
        $tr = [];
        foreach ($matchs as $name => $value) {
            if (isset($this->_routeParams[$name])) {
                $tr[$this->_routeParams[$name]] = $value;
                unset($params[$name]);
            } elseif (isset($this->_paramRules[$name])) {
                $params[$name] = $value;
            }
        }
        if ($this->_routeRule !== null) {
            $route = strtr($this->route, $tr);
        } else {
            $route = $this->route;
        }

        //TODO Yii::debug("Request parsed with URL rule: {$this->name}", __METHOD__);

        if ($normalized) {
            //pathInfo已经转成了标准格式-我们还需要将路由也转成标准格式
            return $this->getNormalizer($manager)->normalizeRoute([$route, $params]);
        }

        return [$route, $params];
    }

    public function createUrl($manager, $route, $params)
    {
        // TODO: Implement createUrl() method.
    }

    /**
     * @param UrlManager $manager Url管理器
     * @return array|bool|false|null|UrlNormalizer
     */
    protected function getNormalizer($manager)
    {
        if ($this->normalizer === null) {
            return $manager->normalizer;
        }

        return $this->normalizer;
    }

    /**
     * @param UrlManager $manager Url管理器
     * @return bool
     */
    protected function hasNormalizer($manager)
    {
        return $this->getNormalizer($manager) instanceof UrlNormalizer;
    }

    /**
     * 遍历[[占位符]]并检查每个占位符是否作为键存在于$matches数组中。
     * 找到时—用匹配参数的适当名称替换此占位符键。
     * 用于[[parseRequest()]， [[createUrl()]]。
     * @param array $matchs  result of `preg_match()` call
     * @return array 使用替换的占位符键输入数组
     * @see placeholders
     * @since 2.0.7
     */
    public function substitutePlaceholderNames($matchs)
    {
        foreach ($this->placeholders as $placeholder => $name) {
            if (isset($matchs[$placeholder])) {
                $matchs[$name] = $matchs[$placeholder];
                unset($matchs[$placeholder]);
            }
        }

        return $matchs;
    }

    public function normalizeRoute($route, $params)
    {

    }

}