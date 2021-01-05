<?php
/**
 * Class AESHelper
 * 作者: su
 * 时间: 2020/10/30 18:15
 * 备注:
 */

namespace App\Helper;


class AESHelper
{
    // AES加密
    public static function opensslEncrypt($data, $method = 'aes-256-cbc')
    {
        $is_encode = config('web.is_encode', true);
        if (!$is_encode) {
            return $data;
        }
        return \base64_encode(openssl_encrypt($data, $method, config('web.aes_key'), OPENSSL_RAW_DATA, config('web.aes_iv')));
    }

    // AES解密
    public static function opensslDecrypt($data, $method = 'aes-256-cbc')
    {
        $is_encode = config('web.is_encode', true);
        if (!$is_encode) {
            return $data;
        }
        return openssl_decrypt(base64_decode($data), $method, config('web.aes_key'), OPENSSL_RAW_DATA, config('web.aes_iv'));
    }

}