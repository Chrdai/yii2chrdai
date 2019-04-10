<?php
/**
 * @purpose: 文件上传类
 * User: daicr
 * Date: 2019/3/28
 * Time: 19:18
 */

namespace common\components;

use core\base\BaseObject;
use core\base\UploadedFile;
use core\helpers\FileHelper;

class UploadFile extends BaseObject
{
    /**
     * 上传成功状态码
     */
    const UPLOAD_SUCCESS = 1;

    /**
     * 上传失败状态码
     */
    const UPLOAD_FAIL = 0;

    /**
     * 验证失败状态码
     */
    const VALIDATE_FAIL = 2;

    /**
     * @var string 文件上传路径
     */
    public $uploadPath = '/tmp';

    /**
     * @var null 文件名称
     */
    public $fileName = null;

    /**
     * @var string 完整路径
     */
    protected $fullUploadedPath;

    /**
     * @var string file控件名称
     */
    public $inputName = 'fileList';

    /**
     * @var array 验证规则
     */
    public $rules = array();

    /**
     * @purpose : 生成上传文件的全路径
     */
    public function init()
    {
        $this->uploadPath =  rtrim($this->uploadPath,'/');

        if(!is_dir($this->uploadPath)){
            FileHelper::createDirectory($this->uploadPath);
        }

        if(!isset($this->fileName)){
            $this->initFileName();
        }

        $this->fullUploadedPath = $this->uploadPath . DIRECTORY_SEPARATOR . $this->fileName;
    }

    /**
     * @purpose: 文件上传
     * @param array $params
     * $parmas可以由2个值作为回调函数用：
     * 1、验证之前的回调`beforeValidate`, 回调函数的参数为“所上传的文件对象”，
     * 2、文件上传成功后的回调`afterSave`, 回调函数的参数为“文件上传成功后的各项信息”，
     * 你可以这样使用它：
     * $uploadFileModel = new UploadFile([
     *   'uploadPath' => '/xxx/xxx',
     * ]);
     * $uploadFileModel->upload([
     *      'beforeValidate' => function($fileObj){
     *          echo $fileObj->name . PHP_EOL;
     *          echo $fileObj->tempName . PHP_EOL;
     *          echo $fileObj->type . PHP_EOL;
     *          echo $fileObj->size . PHP_EOL;
     *          echo $fileObj->error . PHP_EOL;
     *          return ['code'=>1, 'msg' => 'ok'];
     *      },
     *      'afterSave' => function($data){
     *          echo $data['file_name'] . PHP_EOL;
     *          echo $data['original_name'] . PHP_EOL;
     *          echo $data['extension'] . PHP_EOL;
     *          echo $data['file_size'] . PHP_EOL;
     *          echo $data['relative_path'] . PHP_EOL;
     *          echo $data['full_uploaded_path'] . PHP_EOL;
     *          return [1, 'ok'];
     *      },
     * ]);
     */
    public function upload(array $params = [])
    {
        $fileObj = UploadedFile::getInstanceByName($this->inputName);
        //初始化beforeValidate的返回值
        $returnBeforeValidate = [
            'code' => 1,
            'msg' => '',
        ];
        if(isset($params['beforeValidate'])){
            if($params['beforeValidate'] instanceof \Closure){
                $returnBeforeValidate = call_user_func($params['beforeValidate'],$fileObj);
            }
        }
        if(!is_array($returnBeforeValidate) && !isset($returnBeforeValidate['code']) && !isset($returnBeforeValidate['msg'])){
            $this->ajaxReturn(self::VALIDATE_FAIL, 'beforeValidate的返回值无法解析');
        }

        if($fileObj->hasProperty('extension')){
            $this->fullUploadedPath .=  '.' . $fileObj->extension;
            if ($fileObj->saveAs($this->fullUploadedPath)) {
                if(isset($params['afterSave'])){
                    if(!empty($params['afterSave'])){
                        $data = [
                            //上传后的文件名
                            'file_name' => $this->fileName,
                            //原文件名
                            'original_name' => $fileObj->baseName,
                            //拓展名
                            'extension' => $fileObj->extension,
                            //文件大小
                            'file_size' => $fileObj->size,
                            //相对上传路径
                            'relative_path' => str_replace($this->uploadPath . '/', '', $this->fullUploadedPath),
                            //全路径
                            'full_uploaded_path' => $this->fullUploadedPath,
                        ];
                        list($status, $info) = call_user_func($params['afterSave'],$data);
                        $this->ajaxReturn($status, $info);
                    }
                }
            } else {
                $this->ajaxReturn(self::UPLOAD_SUCCESS, '文件上传成功');
            }
        } else {
            $this->ajaxReturn(self::UPLOAD_FAIL, '没有获取到extension属性');
        }
    }

    /**
     * @purpose: 初始化文件名
     */
    public function initFileName()
    {
        $this->fileName = date('YmdHis') . $this->getMillisecond();

    }

    /**
     * @purpose: 生成为秒数
     * @return float
     */
    public function getMillisecond()
    {
        list($micro, $second) =  explode(" ",microtime());
        return round($micro * 1000);
    }

    /**
     * @prpose: ajax返回
     * @param int $status 状态码
     * @param mixed $info 返回的消息内容
     */
    public function ajaxReturn($status, $info)
    {
        $data = [
            'status' => $status,
            'message' => $info,
        ];
        echo json_encode($data);
    }

}