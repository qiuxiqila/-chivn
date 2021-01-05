<?php
/**
 * Class GrpcErrorHelper
 * 作者: su
 * 时间: 2020/12/30 11:24
 * 备注: grpc错误码
 */

namespace App\Helper;


class GrpcErrorHelper
{
    // 操作失败（后台可以通过msg提示用户失败原因）
    const FAIL_CODE = 1;
    // 操作成功
    const SUCCESS_CODE = 0;

    // 默认返回格式字段名
    const RET_CODE = 'errCode';
    const RET_MSG = 'msg';
    const RET_DATA = 'data';
    const RET_TOTAL = 'totalItem';

    const STR_SUCCESS = 'success';

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