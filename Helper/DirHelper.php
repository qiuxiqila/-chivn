<?php
/**
 * Class DirHelper
 * 作者: su
 * 时间: 2020/11/30 15:04
 * 备注: 创建目录
 */

namespace App\Helper;


class DirHelper
{
    /**
     * 创建文件夹
     * @param     $dir
     * @param int $mode
     * @return bool
     */
    public static function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return true;
        if (!@self::mkdirs(dirname($dir), $mode)) return false;
        return @mkdir($dir, $mode);
    }

    /**
     * 递归删除文件夹
     * @param $dir
     */
    public static function rmdirs($dir)
    {
        // 打开指定目录
        if ($handle = @opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if (($file == ".") || ($file == "..")) {
                    continue;
                }
                if (is_dir($dir . '/' . $file)) {
                    self::rmdirs($dir . '/' . $file); // 递归
                } elseif (file_exists($dir . '/' . $file)) {
                    unlink($dir . '/' . $file); // 删除文件
                }
            }
            @closedir($handle);
            rmdir($dir);
        }
    }

}