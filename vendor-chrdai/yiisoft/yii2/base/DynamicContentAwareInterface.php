<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/9/12
 * Time: 15:40
 */

namespace yii\base;

/**
 * DynamicContentAwareInterface是持[[View]]动态内容特性的接口
 * Interface DynamicContentAwareInterface
 * @package yii\base
 */
interface DynamicContentAwareInterface
{
    /**
     * @return mixed 返回动态内容的placeholders。这个方法内部用于实现内容缓存特性。
     */
    public function getDynamicPlaceholders();

    /**
     * 为动态内容设置placeholders。这个方法在内部用于实现内容缓存特性。
     * @param array $placeholders
     */
    public function setDynamicPlaceholders($placeholders);

    /**
     * 为动态内容添加 placeholder。此方法在内部用于实现内容缓存特性。
     * @param string $name the placeholder name.
     * @param string $statements 用于生成动态内容的PHP语句
     * @return mixed
     */
    public function addDynamicPlaceholders($name, $statements);
}