<?php

namespace Cqcqs\Logger\Providers;

use Laravel\Lumen\Application as LumenApplication;
use App\Components\LogManager;
use Illuminate\Support\ServiceProvider;

class LoggerProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->defineTraceId();

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
        //
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
}
