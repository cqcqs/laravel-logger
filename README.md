## Install

```bash
composer require cqcqs/laravel-logger
```

## Use

### Init

```bash
php artisan logger:publish
```

该命令会自动生成 `config/logger.php` 配置文件，并清除配置缓存

**config/logger.php**

```php
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
```

### Provider

**config/app.php**

```php
Cqcqs\Logger\Providers\LoggerProvider::class
```

## 注意

> Lumen 需手动创建配置文件 config/logger.php，并在项目中注册 Provider

```
$app->register(Cqcqs\Logger\Providers\LogServiceProvider::class);
```
