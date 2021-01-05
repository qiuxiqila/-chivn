<?php

namespace App\Helper;

use App\Controller\AbstractController;
use App\Exception\BusinessException;
use Hyperf\Utils\Context;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * 业务公共类
 */
class BaseHelper
{
    const PREFIX = "base_helper:";

    /**
     * 获取客户端ip
     * @return string
     */
    public static function getClientIp(): string
    {
        return Context::get(self::PREFIX . 'client_ip', '');
    }

    /**
     * 设置客户端ip
     * @param ServerRequestInterface $request
     */
    public static function setClientIp(ServerRequestInterface $request)
    {
        $params = $request->getServerParams();
        if (!empty($request->getHeader("x-real-ip"))) {
            $ip = $request->getHeader("x-real-ip");
            $ip = is_array($ip) ? $ip[0] : $ip;
            Context::set(self::PREFIX . 'client_ip', (string)$ip);
        } elseif (!empty($params['remote_addr'])) {
            Context::set(self::PREFIX . 'client_ip', (string)($params['remote_addr']));
        }
    }

    /**
     * 获取本机真实ip
     * @return string
     */
    public static function getLocalRealIp(): string
    {
        return Context::get(self::PREFIX . 'local_real_ip', '');
    }

    /**
     * 设置本机真实ip(仅支持nginx反代下获取)
     * @param ServerRequestInterface $request
     */
    public static function setLocalRealIp(ServerRequestInterface $request)
    {
        Context::set(self::PREFIX . 'local_real_ip', $request->getServerParams()['remote_addr'] ?? '');
    }

    /**
     * 获取本服务代理的域名或ip
     * @return string
     */
    public static function getHost(): string
    {
        return Context::get(self::PREFIX . 'host', '');
    }

    /**
     * 设置本服务代理的域名或ip
     * @param ServerRequestInterface $request
     */
    public static function setHost(ServerRequestInterface $request)
    {
        $host = $request->getHeader("host")[0] ?? '';

        if(!filter_var(ltrim(ltrim($host,'http://'),'https://'), FILTER_VALIDATE_IP)) {
            $host .= config('routes_prefix');
        }

        //判断host是否带有http
        if(strpos($host,'http') == false && strpos($host,'https') == false ){
            $host = 'http://'.$host;
        }
        Context::set(self::PREFIX.'host',(string)$host);
    }

}