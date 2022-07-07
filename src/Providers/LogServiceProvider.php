<?php

namespace Cqcqs\Logger\Providers;

use Cqcqs\Logger\Commands\PublishCommand;
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

        $this->commands($this->commands);

        $this->app->singleton('log', function ($app) {
            if ($app instanceof LumenApplication) {
                // Lumen Application
                $app->configure('logging');
                $app->configure('logger');
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
