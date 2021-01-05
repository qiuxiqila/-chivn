<?php
/**
 * Class WebsocketRoute
 * 作者: su
 * 时间: 2021/1/5 17:21
 * 备注: WebScoket路由注解
 */

namespace Chive\Annotation;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class WebsocketRoute extends AbstractAnnotation
{
    /**
     * @var string 指定server
     */
    public $server = '';
}