<?php

namespace app\modules\standard\controllers;

use common\components\Controller;
use common\components\UploadFile;
use function foo\func;

/**
 * Default controller for the `standard` module
 */
class ProfileController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $userInfo = ['photo'=>''];
//        $this->layout=false;
        return $this->render('index',[
            'userInfo' => $userInfo,
        ]);
    }

    public function actionUpload()
    {
        $uploadFileModel = new UploadFile([
            'uploadPath' => '/var/www/html/basic/web/upload/',
        ]);

        $uploadFileModel->upload([
            'beforeValidate' => function($fileObj){
                return ['code'=>1, 'msg'=>'ok'];
            },
            'afterSave' => function($data){

                return [1,'success'];
            },
        ]);
    }



}
