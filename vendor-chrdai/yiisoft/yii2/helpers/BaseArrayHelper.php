<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/8/8
 * Time: 17:18
 */

namespace yii\helpers;

/**
 * BaseArrayHelper 为 yii\helpers\ArrayHelper 提供了具体的实现方法。
 * 请不要直接使用 BaseArrayHelper 类。使用 yii\helpers\ArrayHelper 类来代替。
 * Class BaseArrayHelper
 * @package yii\helpers
 */
class BaseArrayHelper
{
    /**
     * 返回一个值，该值指示给定数组是否是关联数组。
     * 如果数组的键都是字符串，那么数组就是关联的。当$allStrings设置为false时，只要数组的键中至少有一个是字符串，那么该数组将被视为关联数组。
     * 注: 空数组不会被认为是关联的。
     * @param array $array 将被检测的数组
     * @param bool $allStrings 是否要将检验数租中所有的key都必须为字符串。
     * @return bool 返回数组是否是关联数组
     */
    public static function isAssociative($array, $allStrings = true)
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }
            return true;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }
}