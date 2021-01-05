<?php

namespace Chive\Helper;


use Hyperf\DbConnection\Db;

//use MongoDB\BSON\UTCDateTime;

/**
 * 公共函数方法类
 */
class CommonHelper
{
    // 默认分页条数
    const DEFAULT_PAGE_SIZE = 10;

    /**
     * 对象数组互转
     * @param object/array
     * @return array
     */
    public static function object_array($array)
    {
        if (is_object($array)) {
            $array = (array)$array;
        }
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                $array[$key] = self::object_array($value);
            }
        }
        return $array;
    }

    /**
     * 创建目录
     * @param     $dir
     * @param int $mode
     * @return bool
     */
    public static function mkdirs($dir, $mode = 0777)
    {
        if (is_dir($dir) || @mkdir($dir, $mode)) return true;
        if (!self::mkdirs(dirname($dir), $mode)) return false;
        return @mkdir($dir, $mode);
    }

    /**
     * @param $num
     * @return string
     * 字节转格式
     */
    public static function getFilesize($num)
    {
        $p = 0;
        $format = 'bytes';
        if ($num > 0 && $num < 1024) {
            $p = 0;
            return number_format($num) . $format;
        }
        if ($num >= 1024 && $num < pow(1024, 2)) {
            $p = 1;
            $format = 'KB';
        }
        if ($num >= pow(1024, 2) && $num < pow(1024, 3)) {
            $p = 2;
            $format = 'MB';
        }
        if ($num >= pow(1024, 3) && $num < pow(1024, 4)) {
            $p = 3;
            $format = 'GB';
        }
        if ($num >= pow(1024, 4) && $num < pow(1024, 5)) {
            $p = 3;
            $format = 'TB';
        }
        $num /= pow(1024, $p);
        return number_format($num, 2) . $format;
    }


    /**
     * 默认分页长度
     * @return int
     */
    public static function getDefaultPageSize()
    {
        return self::DEFAULT_PAGE_SIZE;
    }

    /**
     * @param $time
     * @return false|string
     * 时间戳格式转换
     */
    public static function dateTime($time)
    {
        return (((int)$time) > 0) ? date('Y/m/d H:i:s', (int)$time) : '';
    }


    /**
     * 特殊格式转换
     * @param array $array
     * @param string $field_name
     * @return array
     */
    public static function mongoArrayByFields($array = [], $field_name = '')
    {
        if (empty($field_name) || empty($array)) return $array;
        //格式整合
        $data = [];
        foreach ($array as $key => $val) {
            !empty($val['_id'][$field_name]) && $data[$val['_id'][$field_name]] = $val;
        }
        return $data;
    }

    /**
     * 获取自增id
     * @param string $table_name 表名
     * @return bool|string
     */
    public static function getId($table_name)
    {
        $redis = make(RedisHelper::class);
        $key = $table_name . '_get_last_id' . '912j38ajad';
        $wait_key = $table_name . '_get_last_id_wait' . '912j38ajad';
        $max_key = $table_name . '_max_id' . '912j38ajad';
        $id = $redis->lpop($key, 'last');
        if (!$id) {
            //不存在先加个等待锁
            if ($token = $redis->lock($wait_key, 2)) {
                //获取最新id
                $max_id = $redis->get($max_key);
                if (empty($max_id)) {
                    //读取数据库
                    $info = Db::table($table_name)->orderBy('id', 'desc')->first(['id']);
                    $max_id = !empty($info) ? $info->id : 0;
                }
                //生成一万个自增id
                $max_id++;
                $data = [];
                for ($i = $max_id; $i <= ($max_id + 10000); $i++) {
                    $data[] = $i;
                }
                $redis->lpush($key, $data);
                //解锁
                $redis->unlock($wait_key, $token);
                //取出自增id
                $id = $redis->lpop($key, 'last');
            } else {
                //递归
                sleep(0.3);
                $id = self::getId($table_name);
            }
        } else {
            //存在更新key
            $redis->set($max_key, $id);
        }
        return $id;
    }
//    /**
//     * 获取mongodb时间戳
//     */
//     static function getISODate(){
//         return new UTCDateTime(strtotime("+8 hours")*1000);//时区转换
//     }

    /**
     * 循环锁机制
     */
    public static function lock($key, $wait_time, $lock_time = 10)
    {
        $redis = make(RedisHelper::class);
        if ($token = $redis->lock($key, $lock_time)) {
            return $token;
        } else {
            //递归
            $lock_time -= $wait_time;
            return self::lock($key, $wait_time, $lock_time);
        }
    }

    /**
     * 生成订单号
     */
    public static function createOrderNo($key='')
    {
        return $key . date('YmdHis') . rand(10000, 99999);
    }

    /**
     * 获取时间，精确到毫秒
     * @return float
     */
    public static function getMicrosecond()
    {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }

    /**
     * 求两个日期之间相差的天数
     * (针对1970年1月1日之后，求之前可以采用泰勒公式)
     * @param string $day1
     * @param string $day2
     * @return number
     */
    public static function diffBetweenTwoDays($str_day, $end_day)
    {
        $second1 = strtotime($str_day);
        $second2 = strtotime($end_day);

        if ($second1 == $second2) return 0;
        if ($second1 < $second2) {
            $tmp = $second2;
            $second2 = $second1;
            $second1 = $tmp;
        }
        return intval(($second1 - $second2) / 86400);
    }

    /**
     * 获取指定月份的第一天开始和最后一天结束的时间戳
     * @param int $y 年份 $m 月份
     * @return array(本月开始时间，本月结束时间)
     *//* 何问起 hovertree.com */
    public static function mFristAndLast($y = "", $m = "")
    {
        if ($y == "") $y = date("Y");
        if ($m == "") $m = date("m");
        $m = sprintf("%02d", intval($m));
        $y = str_pad(intval($y), 4, "0", STR_PAD_RIGHT);

        $m > 12 || $m < 1 ? $m = 1 : $m = $m;
        $firstday = strtotime($y . $m . "01000000");
        $firstdaystr = date("Y-m-01", $firstday);
        $lastday = strtotime(date('Y-m-d', strtotime("$firstdaystr +1 month -1 day")));
        return array("start_time" => $firstday, "end_time" => $lastday);
    }

    //功能：计算两个时间戳之间相差的日时分秒
    //$begin_time  开始时间戳
    //$end_time 结束时间戳
    public static function timediff($begin_time, $end_time, $format = false)
    {
        if ($begin_time < $end_time) {
            $starttime = $begin_time;
            $endtime = $end_time;
        } else {
            $starttime = $end_time;
            $endtime = $begin_time;
        }
        //计算天数
        $timediff = $endtime - $starttime;
        $days = intval($timediff / 86400);
        //计算小时数
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);
        //计算分钟数
        $remain = $remain % 3600;
        $mins = intval($remain / 60);
        //计算秒数
        $secs = $remain % 60;

        $res = array("day" => $days, "hour" => $hours, "min" => $mins, "sec" => $secs);
        return $res;

    }

    //AES加密
    public static function opensslEncrypt($data, $aes_key = '', $aes_iv = '', $method = 'aes-256-cbc')
    {
        if (empty($aes_key)) {
            $aes_key = config('web.aes_key');
        }
        if (empty($aes_iv)) {
            $aes_iv = config('web.aes_iv');
        }
        return \base64_encode(openssl_encrypt($data, $method, $aes_key, OPENSSL_RAW_DATA, $aes_iv));
    }

    //AES解密
    public static function opensslDecrypt($data, $aes_key = '', $aes_iv = '', $method = 'aes-256-cbc')
    {
        if (empty($aes_key)) {
            $aes_key = config('web.aes_key');
        }
        if (empty($aes_iv)) {
            $aes_iv = config('web.aes_iv');
        }
        return openssl_decrypt(\base64_decode($data), $method, $aes_key, OPENSSL_RAW_DATA, $aes_iv);
    }

    /**
     * 多个字符串按指定规则拼接(根据值排序)
     * @param array  $string_lists
     * @param string $order
     * @param string $symbol
     * @return string
     */
    public static function joinString($string_lists=[],$order='sort',$symbol = '-')
    {
        switch ($order){
            case 'sort':
                sort($string_lists);
                break;
            case 'asort':
                asort($string_lists);
                break;
        }
        return implode($symbol,$string_lists);
    }
}