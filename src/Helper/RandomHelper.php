<?php
/**
 * Class RandomHelper
 * 作者: su
 * 时间: 2020/11/23 14:17
 * 备注: 生成随机数
 */

namespace Chive\Helper;


class RandomHelper
{
    // 随机数长度
    const LENGTH = 4;
    // 随机字符
    const PATTERN = 'QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm';

    /**
     * 获取加密随机数
     * @param int $length
     * @return string
     */
    static public function getRandom($length = self::LENGTH)
    {
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= self::PATTERN[mt_rand(0, strlen(self::PATTERN) - 1)];
        }
        return $str;
    }

    /**
     * 生成唯一的32位字符串
     * @return string
     */
    public static function uniqidStr()
    {
        return md5(uniqid(microtime(true), true));
    }

    /**
     * 获得随机字符串【速度最快】
     * @param int  $len     需要的长度
     * @param bool $special 是否需要特殊符号
     * @return string       返回随机字符串
     */
    public static function getRandomStr($len = self::LENGTH, $special = false)
    {
        $chars = array(
            "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
            "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
            "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
            "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
            "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
            "3", "4", "5", "6", "7", "8", "9"
        );

        if ($special) {
            $chars = array_merge($chars, array(
                "!", "@", "#", "$", "?", "|", "{", "/", ":", ";",
                "%", "^", "&", "*", "(", ")", "-", "_", "[", "]",
                "}", "<", ">", "~", "+", "=", ",", "."
            ));
        }

        $charsLen = count($chars) - 1;
        shuffle($chars);                            //打乱数组顺序
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $charsLen)];    //随机取出一位
        }
        return $str;
    }


}