<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/12
 * Time: 18:03
 */

namespace yii\helpers;

use Yii;

class BaseFileHelper
{
    /**
     * 返回指定文件的本地化版本。
     *
     * 搜索基于指定的语言代码。特别是将在子目录下查找同名文件
     * 其名称与语言代码相同。例如，给定文件“path/to/view.php”和语言代码“zh-CN”，本地化后的文件将被查找为
     * "path/to/zh-CN/view.php"。如果没有找到该文件，它将尝试使用语言代码进行回退,即“zh”。"path/to/zh/view.php"。如果没有找到，则返回原始文件。
     * @param string $file 如果目标语言和源语言代码相同，将返回原始文件。
     * @param string $language 文件应该本地化到的目标语言。如果没有设置，将使用[[\yii\base\Application::language]]的值。
     * @param string $sourceLanguage 匹配的本地化文件，如果没有找到本地化版本，则为原始文件。如果目标和源语言代码相同，则返回原始文件。
     * @return string
     */
    public static function localize($file, $language = null, $sourceLanguage = null)
    {
        if ($language === null) {
            $language = Yii::$app->language;
        }
        if ($sourceLanguage === null) {
            $sourceLanguage = Yii::$app->sourceLanguage;
        }
        if ($language === $sourceLanguage) {
            return $file;
        }

        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($file);
        if (is_file($desiredFile)) {
            return $desiredFile;
        }

        $language = substr($language, 0 ,2);
        if ($language === $sourceLanguage) {
            return $file;
        }
        $desiredFile = dirname($file) . DIRECTORY_SEPARATOR . $language . DIRECTORY_SEPARATOR . basename($file);

        return is_file($desiredFile) ? $desiredFile : $file;
    }
}