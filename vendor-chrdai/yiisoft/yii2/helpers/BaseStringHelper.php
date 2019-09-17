<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/9
 * Time: 15:08
 */

namespace yii\helpers;

/**
 * BaseStringHelper 为 yii\helpers\StringHelper 提供了具体的实现。
 * 不要使用 BaseStringHelper。使用 yii\helpers\StringHelper。
 * Class BaseStringHelper
 * @package yii\helpers
 */
class BaseStringHelper
{

    /**
     * 检查传递的字符串是否与给定的 shell 通配符模式匹配。 此函数使用 PCRE 模拟 \yii\helpers\fnmatch()，这在某些环境中可能不可用。
     * @param string $pattern Shell 通配符模式。
     * @param string $string 待测试的字符串。
     * @param array $options 匹配选项。有效选项包括：
     * caseSensitive：bool，模式是否应区分大小写。默认是 true。
     * escape：bool，是否启用了反斜杠转义。默认是 true。
     * filePath：bool，字符串中的斜杠是否仅与给定模式中的斜杠匹配。默认是 false。
     * @return bool|int
     */
    public static function matchWildcard($pattern, $string, $options = [])
    {
        if ($pattern === '*' && empty($options['filePath'])) {
            return true;
        }

        $replacements = [
            '\\\\\\\\' => '\\\\',
            '\\\\\\*' => '[*]',
            '\\\\\\?' => '[?]',
            '\*' => '.*',
            '\?' => '.',
            '\[\!' => '[^',
            '\[' => '[',
            '\]' => ']',
            '\-' => '-',
        ];

        if (isset($options['escape']) && !$options['escape']) {
            unset($replacements['\\\\\\\\']);
            unset($replacements['\\\\\\*']);
            unset($replacements['\\\\\\?']);
        }

        if (!empty($options['filePath'])) {
            $replacements['\*'] = '[^/\\\\]*';
            $replacements['\?'] = '[^/\\\\]';
        }

        $pattern = strtr(preg_quote($pattern, '#'), $replacements);
        $pattern = '#^' . $pattern . '$#us';

        if (isset($options['caseSensitive']) && !empty($options['caseSensitive'])) {
            $pattern .= 'i';
        }

        return preg_match($pattern, $string);
    }
}