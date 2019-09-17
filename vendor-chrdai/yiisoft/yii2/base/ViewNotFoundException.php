<?php
/**
 * Created by PhpStorm.
 * User: daicr
 * Date: 2019/7/24
 * Time: 11:36
 */

namespace yii\base;


class ViewNotFoundException extends Exception
{
    /**
     * @return string 用户友好
     */
    public function getName()
    {
        return 'View Not Found';
    }

}