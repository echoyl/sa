<?php

namespace Echoyl\Sa\Http\Middleware;

use Echoyl\Sa\Helpers\ResponseEnum;
use Echoyl\Sa\Services\AdminService;
use Closure;

class PermCheck
{
    public function handle($request,Closure $next)
    {
        if(!AdminService::checkAuth())
        {
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_PERM;
            return response()->json(['code'=>$code,'msg'=>$msg]);
        }
        return $next($request);
    }
}
