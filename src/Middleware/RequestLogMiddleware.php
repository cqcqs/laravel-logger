<?php

namespace Cqcqs\Logger\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        try{
            self::log($request,$response);
        }catch (\Exception $e){
            Log::channel(self::channel())->error($e->getMessage(), ['exception' => $e]);
        }
        return $response;
    }

    public static function log(Request $request, $response)
    {
        $uri = $request->getRequestUri();
        $routeName = $request->route()->getName();
        $exceptRoutes = self::config('except_routes');
        if($exceptRoutes && !in_array($routeName, $exceptRoutes)) {
            return;
        }
        $header = [];
        $body=[];
        if(self::config('request.header')){
            $header = $request->headers->all();
        }
        if(self::config('request.body')){
            $body = $_POST;
        }
        if(self::config('request.response') && ($response instanceof JsonResponse)){
            $responseData = $response->getContent();
        }else{
            $responseData = '';
        }
        $data =  compact('uri','header','body','responseData');
        if($data) {
            Log::channel(self::channel())->info('request-log',$data);
        }
    }

    /**
     * @param string $key
     * @param $default
     * @return mixed|\Illuminate\Config\Repository
     */
    private static function config(string $key, $default = null)
    {
        return config("logger.{$key}", $default);
    }

    /**
     * @return mixed
     */
    private static function channel()
    {
        return self::config('request.channel');
    }
}
