<?php

namespace app\common\middleware;

class Cros
{
    public function handle($request, \Closure $next)
    {
        header("Access-Control-Allow-Origin: * ");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE");

        return $next($request);
    }
}
