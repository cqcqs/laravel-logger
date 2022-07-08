<?php

namespace Cqcqs\Logger\Providers;

use Cqcqs\Logger\Commands\PublishCommand;
use Cqcqs\Logger\Middleware\RequestLogMiddleware;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Log;
use Laravel\Lumen\Application as LumenApplication;
use Cqcqs\Logger\Components\LogManager;
use Illuminate\Support\ServiceProvider;
use Exception;

class LogServiceProvider extends ServiceProvider
{
    protected $commands = [
        PublishCommand::class
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // 注册应用依赖，加载中间件、配置、脚本
        $this->registerService();

        // 配置日志库
        $this->setRequestLoggingChannel();

        // define trace id
        $this->defineTraceId();

        $this->app->singleton('log', function ($app) {
            return new LogManager($app);
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPublishing();
    }

    /**
     * Define Trace ID
     *
     * @return void
     */
    private function defineTraceId()
    {
        if (!defined('TRACE_ID')) {
            if (php_sapi_name() === 'cli') {
                define('TRACE_ID', "console-" . time());
            } else {
                define('TRACE_ID', $_SERVER['TRACE_ID'] ?? $_SERVER['REQUEST_TIME']);
            }
        }
    }

    /**
     * 注册应用依赖，加载中间件、配置
     * @return void
     * @throws Exception
     */
    private function registerService()
    {
        $app = $this->app;
        if ($app instanceof LaravelApplication) {
            $this->commands($this->commands);
            // Register Middleware
            $httpKernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
            $httpKernel->pushMiddleware(RequestLogMiddleware::class);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('logging');
            $app->configure('logger');
            // Register Middleware
            $app->middleware(RequestLogMiddleware::class);
        } else {
            Log::error('Project must be Laravel or Lumen.');
        }
    }

    /**
     * 配置日志库
     *
     * @return void
     */
    private function setRequestLoggingChannel()
    {
        $requestLog = config('logger.request.log');
        config(['logging.channels.request_log' => [
            'driver' => data_get($requestLog, 'driver') ?? 'daily',
            'path' => data_get($requestLog, 'path') ?? storage_path('logs/in-out.log'),
            'level' => data_get($requestLog, 'level') ?? 'info',
        ]]);
    }

    /**
     * 资源发布注册.
     *
     * @return void
     */
    protected function registerPublishing()
    {
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config' => config_path()], 'cqcqs-logger-config');
        }
    }
}
