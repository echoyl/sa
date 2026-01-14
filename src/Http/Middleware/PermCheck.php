<?php

namespace Echoyl\Sa\Http\Middleware;

use Closure;
use Echoyl\Sa\Helpers\ResponseEnum;
use Echoyl\Sa\Services\AdminService;

class PermCheck
{
    public function handle($request, Closure $next)
    {
        if (! AdminService::checkAuth()) {
            [$code,$msg] = config('sa.responseEnum.CLIENT_HTTP_UNAUTHORIZED_PERM', ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_PERM);

            return response()->json(['code' => $code, 'msg' => $msg]);
        }

        return $next($request);
    }
}
