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

use App\Middleware\WsTokenMiddleware;

$list   = [
    'http' => [
        \Hyperf\Session\Middleware\SessionMiddleware::class,
        \Hyperf\Validation\Middleware\ValidationMiddleware::class,
        \Chive\Middleware\BaseMiddleware::class,
    ],
];
if (env('RECORD_REQUEST', false) == true) {
    $list['http'][] = \Chive\Middleware\RecordRequestMiddleware::class;
}
// 返回YApi接口格式
if (env('YAPI_FORMAT', false) == true) {
    $list['http'][] = \Chive\Middleware\YApiMiddleware::class;
}

$list['ws'] = [
    WsTokenMiddleware::class, // ws token验证
];
return $list;
