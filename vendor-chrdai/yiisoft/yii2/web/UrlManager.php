<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/9
 * Time: 16:42
 */

namespace yii\web;


class UrlManager
{
    /**
     * @var bool 是否开启url美化，开启后对用户更加友好,默认为false
     * "/index.php?r=news%2Fview&id=100" => "/news/view/id=100"
     */
    public $enablePrettyUrl = false;

    /**
     * 当[[enablePrettyUrl]]为“true”时，他规定了创建和解析url的规则。
     * 只有当[[enablePrettyUrl]]为 true 时才使用此属性。数组中的每个元素都是用于创建单个URL规则的配置数组。
     * 配置将在用于创建规则对象之前首先与[[ruleConfig]]合并。
     *
     * 如果规则只指定[[UrlRule::pattern|pattern]] 和[[UrlRule::route|route]]: " pattern' => 'route' '，
     * 则可以使用一种特殊的快捷格式。也就是说，不使用configuration 数组，可以使用 key 来表示正则和对应路由的值。
     * 例如：`'post/<id:\d+>' => 'post/view'`.
     *
     * 对于RESTful路由，上述快捷方式格式还允许您指定[[UrlRule::verb|HTTPverb]]，该规则应该适用。
     * 您可以通过在模式前面加上空格来实现这一点。
     * 例如：`'PUT post/<id:\d+>' => 'post/update'`.
     *
     * 可以用逗号分隔多个动作
     * 例如：`'POST,PUT post/index' => 'post/create'`.
     *
     * 支持的动作（verb）有 GET, HEAD, POST, PUT, PATCH and DELETE.
     * 注意，[[UrlRule::mode|mode]]在以这种方式指定verb时将被设置为PARSING_ONLY，
     * 因此，您通常不会为普通GET请求指定动词。
     *
     * 下面是一个配饰RESTful CURD Controller的示例：
     * ```php
     * [
     *     'dashboard' => 'site/index',
     *
     *     'POST <controller:[\w-]+>s' => '<controller>/create',
     *     '<controller:[\w-]+>s' => '<controller>/index',
     *
     *     'PUT <controller:[\w-]+>/<id:\d+>'    => '<controller>/update',
     *     'DELETE <controller:[\w-]+>/<id:\d+>' => '<controller>/delete',
     *     '<controller:[\w-]+>/<id:\d+>'        => '<controller>/view',
     * ];
     * ```
     * 注：如果在创建UrlManager对象之后修改此属性，请确保用规则对象而不是规则配置填充数组。
     * @var array
     */
    public $rules = [];

    /**
     * @var string 当$enablePrettyUrl=true时，使用的url后缀。
     * 如 .html ,代表请求的是一个静态的html文件。
     */
    public $suffix;

    /**
     * @var UrlNormalizer|array|string|false 这个UrlManager使用的[[UrlNormalizer]]的配置。
     * 默认值为' false '，这意味着将跳过规范化。
     * 如果希望启用URL规范化，应该手动配置此属性。
     *
     * 例如：
     *
     * ```php
     * [
     *     'class' => 'yii\web\UrlNormalizer',
     *     'collapseSlashes' => true,
     *     'normalizeTrailingSlash' => true,
     * ]
     * ```
     */
    public $normalizer = false;

    /**
     * @var bool 是否启用严格解析。如果启用了严格解析，则传入
     * 被请求的URL必须匹配至少一条[[规则]]，才能被视为有效的请求。
     * 否则，请求的路径信息部分将被视为被请求的路由。
     * 只有当[[enablePrettyUrl]]为“true”时才使用此属性。
     */
    public $enableStrictParsing = false;

    /**
     * @var string 路由的GET参数名。只有当[[enablePrettyUrl]]为' false '时才使用此属性。
     */
    public $routeParam = 'r';

    /**
     * 解析用户请求。
     * @param Request $request 请求组件
     * @return array|bool 路由和相关参数。参数总是空值，如果[[enablePrettyUrl]]为' false '。如果当前请求不能成功解析，则返回“false”。
     */
    public function parseRequest($request)
    {
        if ($this->enablePrettyUrl) {
            foreach ($this->rules as $rule) {
                /* @var UrlRule $rule  */
                $result = $rule->parseRequest($this, $request);
                //TODO YII_DEBUG
                if ($result !== false) {
                    return $result;
                }
            }
        }

        if ($this->enableStrictParsing) {
            return false;
        }

        //TODO Yii::debug('No matching URL rules. Using default URL parsing logic.', __METHOD__);

        $suffix = (string)$this->suffix;
        $pathInfo = $request->getPathInfo();
        $normalized = false;
        if ($this->normalizer !== false) {
            $pathInfo = $this->normalizer->normalizePathInfo($pathInfo, $suffix, $normalized);
        }
        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($this->suffix);
            if (substr_compare($pathInfo, $this->suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    //不允许单独使用后缀
                    return false;
                } else {
                    //后缀不匹配
                    return false;
                }
            }
        }

        if ($normalized) {
            //pathInfo已经转成了标准格式-我们还需要将路由也转成标准格式
            return $this->normalizer->normalizeRoute([$pathInfo, []]);
        }

        //TODO Yii::debug('Pretty URL not enabled. Using default URL parsing logic.', __METHOD__);

        $route = $request->getQueryParam($this->routeParam, '');

        if (is_array($route)) {
            $route = '';
        }

        return [(string) $route, []];
    }
}