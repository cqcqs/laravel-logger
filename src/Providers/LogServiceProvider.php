<?php

namespace Cqcqs\Logger\Providers;

use Cqcqs\Logger\Commands\PublishCommand;
use Cqcqs\Logger\Middleware\RequestLogMiddleware;
use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use App\Components\LogManager;
use Illuminate\Support\ServiceProvider;

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
        $this->defineTraceId();

        $this->setRequestLoggingChannel();

        $this->commands($this->commands);

        $this->app->singleton('log', function ($app) {
            if ($app instanceof LumenApplication) {
                // Lumen Application
                $app->configure('logging');
                $app->configure('logger');
                // Register Middleware
                $this->app->middleware(RequestLogMiddleware::class);
            } elseif ($app instanceof LaravelApplication) {
                // Register Middleware
                $httpKernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
                $httpKernel->pushMiddleware(RequestLogMiddleware::class);
            }
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
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../../config' => config_path()], 'cqcqs-logger-config');
        }
    }
}
