<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace App\Controller;

use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Chive\Helper\VerifyHelper;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Container\ContainerInterface;
use Wuxian\WebUtils\WebUtilsTrait;

abstract class AbstractController
{
    /**
     * @Inject()
     * @var VerifyHelper $verifyHelper
     */
    protected $verifyHelper;

    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $response;

    /**
     * 成功返回
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function success($data = [])
    {
        if(!is_array($data)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '返回格式必须是数组');
        }
        // 分页
        if (isset($data['data']) && isset($data['total'])) {
            return $this->response->json(WebUtilsTrait::send(ErrorHelper::SUCCESS_CODE, ErrorHelper::STR_SUCCESS, $data['data'], $data['total']));
        }
        return $this->response->json(WebUtilsTrait::send(ErrorHelper::SUCCESS_CODE, ErrorHelper::STR_SUCCESS, $data));
    }

    /**
     * 失败返回
     * @param int $code
     * @param string $message
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    protected function fail($code = ErrorHelper::FAIL_CODE, $message = 'fail', $data = [])
    {
        if(!is_array($data)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '返回格式必须是数组');
        }
        return $this->response->json(WebUtilsTrait::send($code, $message, $data));
    }
}
