<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2018/12/29
 * Time: 15:20
 */

namespace common\web;



class User extends \yii\web\User
{
    public $adminUsers = ['admin'];


    /**
     * 获取identified对象，此方法主要为了IDE友好
     * @param bool $autoRenew
     * @return Identified|null
     */
    public function getIdentity($autoRenew = true)
    {
        return parent::getIdentity($autoRenew);
    }


    /*
     * @purpose :   是否是超级管理员
     * @return  :   boolean true|false
     * */
    public function isSupperUser($user)
    {
        return in_array($this->getState('username'),$this->adminUsers);
    }



    /*
     * @purpose :   获取用户的状态信息，比如用户的姓名信息
     * \Yii::$app->user->getState('username');
     * @return  :   boolean true|false
     * */
    public function getState($name)
    {
        $identity = $this->getIdentity();
        return $identity <> null ?  $identity->getState($name) ? null ;
    }

}