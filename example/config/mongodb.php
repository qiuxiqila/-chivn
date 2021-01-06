<?php
return [
    'default' => [
        'username'      => env('MONGODB_USERNAME', 'root'),
        'password'      => env('MONGODB_PASSWORD', 'root'),
        'host'          => env('MONGODB_HOST', '127.0.0.1'),
        'port'          => env('MONGODB_PORT', 27017),
        'db'            => env('MONGODB_DB', 'test'),
        'authMechanism' => 'SCRAM-SHA-256',
        //设置复制集,没有不设置
        //'replica' => 'rs0',
        'pool'          => [
            'min_connections' => 3,
            'max_connections' => 1000,
            'connect_timeout' => 10.0,
            'wait_timeout'    => 10.0,
            'heartbeat'       => -1,
            'max_idle_time'   => (float)env('MONGODB_MAX_IDLE_TIME', 60),
        ],
    ],
];