<?php
/**
 * @purpose: 文件上传类
 * User: daicr
 * Date: 2019/3/28
 * Time: 19:18
 */

namespace core\base;


class UploadedFile extends BaseObject
{
    /**
     * @var string 文件名称
     */
    public $name;

    /**
     * @var string 存储在服务器的文件临时副本的名称，本次请求完毕后，会删除
     */
    public $tempName;

    /**
     * @var string 文件类型
     */
    public $type;

    /**
     * @var int 文件大小
     */
    public $size;

    /**
     * @var int 文件上传导致的错误代码
     */
    public $error;

    /**
     * @var mixed 存储文件的各项属性
     */
    private static $_files;

    /**
     * @prupose: 根据控件名称返回所上传文件的实例
     * @param string $name file控件名称
     * @return null|static 所上传文件的实例
     * 如果所指定的控件名称不存在，将返回 null
     */
    public static function getInstanceByName($name)
    {
        $files = self::loadFiles();
        return isset($files[$name]) ? new static($files[$name])  : null;
    }


    /**
     * @prupose: 从$_FILES创建上传文件的实例
     * @return array|mixed
     */
    public static function loadFiles()
    {
        if(self::$_files === null){
            self::$_files = [];
            if(isset($_FILES) && is_array($_FILES)){
                foreach ($_FILES as $class => $info){
                    self::loadFilesRecursive($class, $info['name'],$info['tmp_name'],$info['type'],$info['size'],$info['error']);
                }
            }
        }
        return self::$_files;
    }

    /**
     * @purpose: 从$_FILES创建上传文件的实例，如果有多个，递归创建
     * @param string $key 标识上传文件的唯一值，将作为实例化的"键"
     * @param mixed $name 由php自动生成的上传文件名称
     * @param mixed $tempNames 由php自动生成的零时文件名称
     * @param mixed $types 由php自动生成的文件类型
     * @param mixed $sizes 由php自动生成的上传文件大小
     * @param mixed $errors 由php自动生成的上传过程中出现的错误码
     */
    public static function loadFilesRecursive($key, $names, $tempNames, $types, $sizes, $errors)
    {
        if(is_array($names)){
            foreach($names as $i => $name){
                self::loadFilesRecursive($key . '[' . $i . ']' , $name, $tempNames[$i], $types[$i], $sizes[$i], $errors[$i]);
            }
        }elseif(intval($errors) !== UPLOAD_ERR_NO_FILE){
            self::$_files[$key] = [
                'name' => $names,
                'tempName' => $tempNames,
                'type' => $types,
                'size' => $sizes,
                'error' => $errors,
            ];
        }
    }

    /**
     * @return string 返回文件的源文件名，如上传 dog.jpg , 则返回 dog
     * 对上传的中文文件也做了处理
     */
    public function getBaseName()
    {
        $pathInfo = pathinfo('_' . $this->name, PATHINFO_FILENAME);
        return mb_substr($pathInfo, 1, mb_strlen($pathInfo, '8bit'), '8bit');
    }

    /**
     * @return string 返回文件的后缀名
     */
    public function getExtension()
    {
        return  strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * @return bool true|false 返回上传文件过程中是否出错
     */
    public function getHaseError()
    {
        return $this->error !== UPLOAD_ERR_OK;
    }

    public function reset()
    {
        self::$_files = null;
    }

    /**
     * @purpose: 保存上传的文件
     * @param string $filePath 上传文件的全路径
     * @param bool $deleteTempFile 保存后是否要删除零时文件
     * 如果为true，在该次请求中将不能再一次保存本次上传的文件
     * @return bool true|false
     */
    public function saveAs($filePath, $deleteTempFile = true)
    {
        //https://www.php.net/manual/zh/features.file-upload.errors.php
        if ($this->error === UPLOAD_ERR_OK) {
            if ($deleteTempFile) {
                return move_uploaded_file($this->tempName, $filePath);
            } elseif (is_uploaded_file($this->tempName)) {
                return copy($this->tempName, $filePath);
            }
        }
    }
}