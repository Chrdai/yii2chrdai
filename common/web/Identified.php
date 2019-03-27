<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2018/12/29
 * Time: 18:15
 */

namespace common\web;

use yii\base\Object;
use yii\web\IdentityInterface;

class Identified extends Object  implements IdentityInterface
{

    /*
     * @puropose    :   存储引擎
     * @time        :   2018-12-29
     * */
    public $store = '';

    /*
     * @puropose    :   存储引擎
     * @param       :   string $name 用户属性名称
     * @time        :   2018-12-29
     * @return      :   string $val 用户该属性的信息
     * */
    public function getState($name)
    {
        switch ($this->store){
            case 'redis':
                break;
            default:
                $val = $this->hasProperty($name) ? $this->$name : null;
        }
        return $val;
    }
}