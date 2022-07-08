<?php

return [
    // 启用状态
    'enable' => true,
    // 需要收集的日志通道，默认所有
    'channels' => [],
    // 日志路径
    'path' => storage_path('logs/all.log'),
    
    // 请求日志
    'request' => [
        // 请求日志状态
        'enable' => true,
        // 需要排除的路由别名
        'except_routes' => [],
        // 头部日志
        'header' => false,
        // body日志
        'body' => false,
        // 响应日志
        'response' => false,
        // 日志
        'log' => [
            'driver' => 'daily',
            'path' => storage_path('logs/io.log'),
            'level' => 'info',
        ]
    ]
];
