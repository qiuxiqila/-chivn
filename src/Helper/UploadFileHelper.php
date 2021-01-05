<?php
/**
 * Class UploadFileHelper
 * 时间: 2020/11/30 10:44
 * 备注: 上传文件
 *
 * .env
 * IMAGES_PATH=public/image/
 */

namespace Chive\Helper;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Psr\Log\LogLevel;

class UploadFileHelper
{
    // 图片上传地址
    const Image_Path = 'public/image/';
    // 图片后缀
    const Image_Suffix = ['jpg', 'png', 'gif', 'jpeg'];
    // 图片上限，-1 不做判断
    const Image_Max_Size = 2 * 1024 * 1024;     // 2M

    // 文件上传地址
    const File_Path = 'public/file/';
    // 文件后缀
    const File_Suffix = ['docx', 'doc', 'pdf', 'txt', 'xls', 'xlsx'];
    // 文件大小，-1不做判断
    const File_Max_Size = 10 * 1024 * 1024;     // 10M

    /** @var int 统一返回格式，0成功，1错误 */
    private const Code_Success = 0;
    private const Code_Fail = 1;

    //ERP文档管理上传地址
    const Document_Path = 'public/document/';
    const Document_Type = 'document';
    // 文件大小，-1不做判断
    const Document_Max_Size = 500 * 1024 * 1024;     // 500M

    /**
     * 获取文件夹上传限制
     * @return array|bool|false|string
     */
    public static function getDocumentMaxSize()
    {
        return env('DOCUMENT_MAX_SIZE', self::Document_Max_Size);
    }

    /**
     * 统一返回格式
     * @param        $code
     * @param string $msg
     * @param string $fileName
     * @param string $path
     * @param int $size 文件大小
     * @param string $type 文件后缀
     * @return array
     */
    private static function returnFormat($code, $msg = '', $fileName = '', $path = '', $size = 0, $type = '')
    {
        return [
            'code'     => $code,
            'msg'      => $msg,
            'fileName' => $fileName,
            'path'     => $path,
            'size'     => $size,
            'type'     => $type,
        ];
    }


    /**
     * 上传图片
     * @param RequestInterface $request
     * @param string $paramName 上传文件参数名
     * @param array $suffix 允许后缀
     * @param float|int $size 图片上限大小，-1不做判断
     * @return array
     */
    public static function uploadImage($request, $paramName = 'image', $suffix = self::Image_Suffix, $size = self::Image_Max_Size)
    {
        $res = self::verifyFile($request, $paramName, $suffix, $size);
        if ($res['code'] == self::Code_Fail) {
            return $res;
        }
        $file      = $request->file($paramName);
        $imageType = $file->getExtension();
        $imageName = self::getRandName($imageType);

        $uploadPath = env('IMAGES_PATH', self::Image_Path);
        if (!DirHelper::mkdirs($uploadPath)) {
            return self::returnFormat(self::Code_Fail, '创建保存目录失败！');
        }
        $path = $uploadPath . $imageName;
        $file->moveTo($path);
        if ($file->isMoved()) {
            chmod($path, 0655);
            return self::returnFormat(self::Code_Success, '', $imageName, $path, $file->getSize(), $imageType);
        }
        return self::returnFormat(self::Code_Fail, '上传失败！');
    }

    /**
     * 上传图片
     * @param RequestInterface $request
     * @param string $paramName 上传文件参数名
     * @param string $type 上传文件类型
     * @param array $suffix 允许后缀
     * @param float|int $size 图片上限大小，-1不做判断
     * @return array
     */
    public static function upload($request, $paramName = 'file', $type = '', $suffix = [], $size = -1)
    {
        $res = self::verifyFile($request, $paramName, $suffix, $size);
        if ($res['code'] == self::Code_Fail) {
            return $res;
        }
        $file      = $request->file($paramName);
        $imageType = $file->getExtension();
        $imageName = self::getRandName($imageType);

        $uploadPath = self::getPath($type);
        if (!DirHelper::mkdirs($uploadPath)) {
            return self::returnFormat(self::Code_Fail, '创建保存目录失败！');
        }
        $path = $uploadPath . $imageName;
        $file->moveTo($path);
        if ($file->isMoved()) {
            chmod($path, 0655);
            return self::returnFormat(self::Code_Success, '', $imageName, $path, $file->getSize(), $imageType);
        }
        return self::returnFormat(self::Code_Fail, '上传失败！');
    }

    /**
     * 返回文件存放地址
     * @param $type
     * @return array|bool|false|mixed|string|void
     */
    public static function getPath($type)
    {
        switch ($type) {
            case 'image' :
                return env('IMAGES_PATH', self::Image_Path);
                break;
            case 'file':
                return env('FILE_PATH', self::File_Path);
                break;
            case 'document':
                return env('DOCUMENT_PATH', self::Document_Path);
                break;
            default:
                return 'public/';
                break;
        }
    }

    /**
     * 验证文件有效性
     * @param RequestInterface $request
     * @param string $paramName 文件参数名
     * @param array $suffix 支持的后缀名
     * @param int $size 大小
     * @return array
     */
    private static function verifyFile($request, $paramName, $suffix, $size)
    {
        $file = $request->file($paramName);
        //判断文件是否存在
        if (!$request->hasFile($paramName)) {
            return self::returnFormat(self::Code_Fail, '上传文件不存在!');
        }
        //判断文件是否有效
        if (!$file->isValid()) {
            return self::returnFormat(self::Code_Fail, '上传文件无效!');
        }
        //校验扩展名
        $extension = $file->getExtension();
        if (!in_array($extension, $suffix)) {
            return self::returnFormat(self::Code_Fail, '只支持' . implode(",", $suffix) . '格式');
        }
        // 验证大小
        if ($size != -1) {
            if ($file->getSize() > $size) {
                return self::returnFormat(self::Code_Fail, '文件请限制在' . CommonHelper::getFilesize($size));
            }
        }
        return self::returnFormat(self::Code_Success);
    }

    /**
     * 生成随机名
     * @param $image_type
     * @return string
     */
    public static function getRandName($image_type)
    {
        $name = RandomHelper::getRandomStr();
        return date('YmdHis') . $name . '.' . $image_type;
    }

    /**
     * 将字节byte转换为B,KB,MB,GB,TB
     * @param $size
     * @return string
     */
    function formatBytes($size)
    {
        $units = array(' B', ' KB', ' MB', ' GB', ' TB');
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . $units[$i];

    }

    /**
     * 打包文件目录zip 下载
     * @param $file_path
     * @param $file_name
     * @return array
     */
    public function downloadFolderZip($file_path,$file_name)
    {
        $file_name_zip = $file_name.'.zip';//定义打包后的包名
        if (!is_dir($file_path)) {
            return self::returnFormat(self::Code_Fail,'下载目录文件不存在!');
        }
        //打包后的文件路径
        $file_path_name_zip = $file_path.$file_name_zip;
        //linux 打包下载 需要先服务器 yum install -y zip
        exec("cd $file_path && zip -r $file_name_zip $file_name");
        if (!file_exists($file_path_name_zip)) {
            return self::returnFormat(self::Code_Fail,'打包Zip格式下载失败!');
        }

        return self::returnFormat(self::Code_Success,$file_path_name_zip);
    }

}