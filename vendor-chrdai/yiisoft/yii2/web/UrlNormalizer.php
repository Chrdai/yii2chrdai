<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/12
 * Time: 14:31
 */

namespace yii\web;

use Yii;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;

class UrlNormalizer extends BaseObject
{
    /**
     * Represents permament redirection during route normalization.
     * @see https://en.wikipedia.org/wiki/HTTP_301
     */
    const ACTION_REDIRECT_PERMANENT = 301;
    /**
     * Represents temporary redirection during route normalization.
     * @see https://en.wikipedia.org/wiki/HTTP_302
     */
    const ACTION_REDIRECT_TEMPORARY = 302;
    /**
     * Represents showing 404 error page during route normalization.
     * @see https://en.wikipedia.org/wiki/HTTP_404
     */
    const ACTION_NOT_FOUND = 404;

    /**
     * @var bool 斜杠是否会被合并，例如：`site///index` 将会被转换为 `site/index`
     */
    public $collapseSlashes;

    /**
     * @var bool 是否应该根据后缀设置对字符串最后的斜杠进行规范化
     */
    public $normalizeTrailingSlash;

    /**
     * @var int|callable|null 在路由规范化期间要执行的操作
     * 可以是如下的值：
     * - `null` - 不会采取任何 action
     * - `301` - 永久重定向
     * - `302` - 临时重定向
     * - `404` - [[NotFoundHttpException]] will be thrown
     * - `callable` - 用户自定义的回调函数, for example:
     *
     *   ```php
     *   function ($route, $normalizer) {
     *       //使用自定义操作进行重定向
     *       $route[1]['oldRoute'] = $route[0];
     *       $route[0] = 'site/redirect';
     *       return $route;
     *   }
     *   ```
     */
    public $action = self::ACTION_REDIRECT_PERMANENT;


    /**
     * 使指定pathInfo规范化。
     * 主要做一下两个规范：
     * 1、去除多余的连续的斜杠。
     * 2、根据 $suffix 的值来确定是否要保留 $pahtInfo 最后的斜杠。
     *
     * @param string $pathInfo 待规范的 $pathInfo
     * @param string $suffix 当前规则后缀
     * @param bool $normalized 如果 $pathInfo 在规范化期间被膝盖，该变量将被设置为' true '
     * @return string 规范后的 $pathInfo
     */
    public function normalizePathInfo($pathInfo, $suffix, &$normalized = false)
    {
        if (empty($pathInfo)) {
            return $pathInfo;
        }

        $sourcePathInfo = $pathInfo;
        if ($this->collapseSlashes) {
            $pathInfo = $this->collapseSlashes($pathInfo);
        }

        if ($this->normalizeTrailingSlash) {
            $pathInfo = $this->normalizeTrailingSlash($pathInfo, $suffix);
        }

        $normalized = $sourcePathInfo !== $pathInfo;

        return $pathInfo;
    }

    /**
     * 将 $pathInfo 中连续的多个斜杆合并成一个斜杠，例如：`site///index` 将会被转换为 `site/index`
     * @param string $pathInfo url中index.php之后?之前的部分
     * @return string
     */
    protected function collapseSlashes($pathInfo)
    {
        return ltrim(preg_replace('#/{2,}#', '/', $pathInfo), '/');
    }

    /**
     * 如果 $suffix(后缀) 为 '/' , 且 $pathInfo的最后一个字符又不是 '/'，则给 $pathInfo 的最后追加一个字符 '/';
     * 如果 $suffix(后缀) 不为 '/' , 且 $pathInfo的最后一个字符又是 '/'，则去掉 $pathInfo 的最后这个 '/' 字符;
     * @param string $pathInfo url中index.php之后?之前的部分
     * @param string $suffix 后缀
     * @return string 根据 $suffix 规范后的 $pathInfo
     */
    protected function normalizeTrailingSlash($pathInfo, $suffix)
    {
        if (substr($suffix, -1) === '/' && substr($pathInfo, -1) !== '/') {
            $pathInfo .= '/';
        } elseif (substr($suffix, '-1') !== '/' && substr($pathInfo, -1) === '/') {
            $pathInfo = rtrim($pathInfo, '/');
        }

        return $pathInfo;
    }

    /**
     * 规范指定的路由
     * @param array $route 待规范的路由
     * @return array 规范后的路由
     * @throws InvalidConfigException 如果使用无效的规范化操作。
     * @throws NotFoundHttpException 如果规范化操作匹配路由不存在。
     * @throws UrlNormalizerRedirectException f规范化需要重定向。
     */
    public function normalizeRoute($route)
    {
        if ($this->action === null) {
            return $route;
        } elseif ($this->action === static::ACTION_REDIRECT_PERMANENT || $this->action === static::ACTION_REDIRECT_TEMPORARY) {
            throw new UrlNormalizerRedirectException([$route[0]] + $route[1], $this->action);
        } elseif ($this->action === static::ACTION_NOT_FOUND) {
            throw new NotFoundHttpException(Yii::t('yii', 'Page not found.'));
        } elseif (is_callable($this->action)) {
            return call_user_func($this->action, $route, $this);
        }

        throw new InvalidConfigException('Invalid normalizer action.');
    }
}