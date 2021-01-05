<?php
/**
 * Created by PhpStorm.
 * User: laizhili
 * Date: 2020/12/14
 * Time: 17:18
 */

namespace Chive\Helper;


class QrCodeHelper
{
    public static function QrCodeReader($path)
    {
        $qrcode = new \Zxing\QrReader($path);  //图片路径
        $text = $qrcode->text(); //返回识别后的文本
        return $text;
    }
}