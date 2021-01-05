<?php
/**
 * Class BusinessException
 * 作者: caiwh
 * 时间: 2020/5/26 10:39
 * 备注:
 */

namespace Chive\Exception;

use Hyperf\Server\Exception\ServerException;
use Throwable;

/**
 * Class BusinessException
 * 作者: caiwh
 * 时间: 2020/5/26 10:39
 * 备注: 业务异常类
 */
class BusinessException extends ServerException
{
    /**
     * BusinessException constructor.
     * @param int            $code
     * @param string|null    $message
     * @param Throwable|null $previous
     */
    public function __construct(int $code, string $message = null, Throwable $previous = null)
    {
//        if (is_null($message)) {
//            $message = ErrorCode::getMessage($code);
//        }

        parent::__construct($message, $code, $previous);
    }
}