<?php

namespace Chive\Helper;

use Firebase\JWT\JWT;

class JwtServiceHelper
{
    const JWT_KEY = 'soGameCarJwtKey';
    const JWT_DOMAIN = 'soGameJwtDomain';

    /**
     * 获取jwtToken
     * @param           $data
     * @param float|int $expire
     * @return string
     */
    static public function getToken($data, $expire = 3600 * 5)
    {
        $domain = env('JWT_DOMAIN', self::JWT_DOMAIN); //domain
        $key    = env('JWT_KEY', self::JWT_KEY); //key
        $time   = time(); //当前时间
        $token  = [
            'iss'  => $domain, //签发者 可选
            'aud'  => $domain, //接收该JWT的一方，可选
            'iat'  => $time, //签发时间
            'nbf'  => $time, //(Not Before)：某个时间点后才能访问，比如设置time+30，表示当前时间30秒后才能使用
            'exp'  => $time + ($expire), //过期时间,这里设置5小时，这里也可以配置化处理
            'data' => $data, //自定义信息，不要定义敏感信息，一般放用户id，足以。
        ];
        return JWT::encode($token, $key); //输出Token
    }

    /**
     * 校验jwt权限API
     * @param string $jwt
     * @return array|bool|string
     */
    static public function checkToken($jwt = '')
    {
        if (empty($jwt)) return false;
        $key = env('JWT_KEY', self::JWT_KEY); //key
        try {
            JWT::$leeway = 60; //当前时间减去60，把时间留点余地
            $decoded     = JWT::decode($jwt, $key, ['HS256']); //HS256方式，这里要和签发的时候对应

            $arr = (array)$decoded;
        } catch (\Exception $e) {
            //Firebase定义了多个 throw new，我们可以捕获多个catch来定义问题，catch加入自己的业务，比如token过期可以用当前Token刷新一个新Token

            return $e->getMessage();
        }
        return (array)$arr['data'];
    }
}












