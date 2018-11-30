<?php

namespace app\common\middleware;

use think\facade\Session;

class Auth
{
    public function handle($request, \Closure $next)
    {
        $module = $request->module();
        if ($module == 'admin' && !Session::has('admin')){
            return redirect('admin/auth/login');
        }

        return $next($request);
    }
}
