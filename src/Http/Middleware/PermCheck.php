<?php

namespace Echoyl\Sa\Http\Middleware;

use Closure;
use Echoyl\Sa\Helpers\ResponseEnum;
use Echoyl\Sa\Services\AdminService;
use Illuminate\Support\Facades\Lang;

class PermCheck
{
    public function handle($request, Closure $next)
    {
        if (! AdminService::checkAuth()) {
            $key = 'sa::response.nopermission';
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_PERM;
            $msg = Lang::has($key) ? __($key) : $msg;

            return response()->json(['code' => $code, 'msg' => $msg]);
        }

        return $next($request);
    }
}
