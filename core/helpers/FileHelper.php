<?php
/**
 * @purpose: 文件和目录助手类
 * User: daicr
 * Date: 2019/3/29
 * Time: 17:17
 */

namespace core\helpers;


class FileHelper
{
    /**
     * @purpose: 创建目录
     * @param string $path 所创建目录的路径
     * @param int $mode 权限
     * @param bool $recursive 如果该路径的父目录不存在，是否需要递归创建
     * @return bool 目录是否创建成功
     * @throws \Exception
     */
    public static function createDirectory($path, $mode = 0755, $recursive = true)
    {
        if(is_dir($path)){
            return true;
        }

        $parentDir = dirname($path);
        if($recursive && !is_dir($parentDir) && $parentDir !== $path){
            static::createDirectory($parentDir, $mode, true);
        }

        try{
            if(! mkdir($path, $mode)){
                return false;
            }
        }catch(\Exception $e){
            if(!is_dir($path)){
                throw new \Exception("Failed to create directory $path, error code:" . $e->getCode() . ', ' . $e->getMessage() . ',at line ' . $e->getLine() . PHP_EOL);
            }
        }

        try{
            return chmod($path, $mode);
        }catch(\Exception $e){
            throw new \Exception("Failed to change perrmissions for direcotry $path, error code:" . $e->getCode() . ', ' . $e->getMessage() . ',at line ' . $e->getLine() . PHP_EOL);
        }
    }
}