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

$appEnv = env('APP_ENV', 'dev');
if ($appEnv == 'dev') {
    $formatter = [
        'class'       => Monolog\Formatter\LineFormatter::class,
        'constructor' => [
            'format'                => null,
            'dateFormat'            => null,
            'allowInlineLineBreaks' => true,
        ],
    ];
    $log_path  = BASE_PATH . '/runtime/logs/';
} else {
    $formatter = [
        'class'       => Monolog\Formatter\JsonFormatter::class,
        'constructor' => [
            'format' => null,
            'dateFormat' => 'Y-m-d H:i:s',
            'allowInlineLineBreaks' => true,
        ],
    ];
    $log_path  = env('LOG_PATH', BASE_PATH);
}

return [
    'default'         => [
        'handler'   => [
            'class'       => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => $log_path . '/hyperf.log',
                'level'    => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => $formatter,
    ],
    // http请求
    'http'            => [
        'handler'   => [
            'class'       => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => $log_path . '/http.log',
                'level'    => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => $formatter,
    ],
    //错误日志
    'error'           => [
        'handler'   => [
            'class'       => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => $log_path . '/error.log',
                'level'    => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => $formatter,
    ],
    //请求第三方日志
    'thirdly'           => [
        'handler'   => [
            'class'       => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => $log_path . '/thirdly.log',
                'level'    => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => $formatter,
    ],
    // 代码打印日志
    'printLog'           => [
        'handler'   => [
            'class'       => Monolog\Handler\RotatingFileHandler::class,
            'constructor' => [
                'filename' => $log_path . '/print.log',
                'level'    => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => $formatter,
    ],
];
