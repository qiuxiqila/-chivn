<?php

namespace Chive\Helper;

use App\Exception\BusinessException;
use Throwable;

/**
 * 业务code和方法类
 */
class ErrorHelper
{
    // 操作失败（后台可以通过msg提示用户失败原因）
    const FAIL_CODE = 0;
    // 操作成功
    const SUCCESS_CODE = 1;
    // 凭证(token)过期、无效（前端会自动跳转到登录界面）
    const TOKEN_ERROR = 2;
    // 无操作权限（前端会显示msg）
    const AUTH_ERROR = 3;


    // 默认返回错误字符串
    const SYSTEM_ERROR = 'system error';
    const STR_SUCCESS = 'success';

    // 默认返回格式字段名
    const RET_CODE = 'code';
    const RET_MSG = 'msg';
    const RET_DATA = 'data';
    const RET_TOTAL = 'total';

    /**
     * 统一返回格式
     * @param     $code
     * @param     $msg
     * @param     $data
     * @param int $total null没分页功能。其他为带分页功能，会返回total字段
     * @return array
     */
    public static function returnFormat($code, $msg, $data, $total = 1)
    {
        return [
            self::RET_CODE  => $code,
            self::RET_MSG   => $msg,
            self::RET_DATA  => $data,
            self::RET_TOTAL => $total,
        ];
    }


}