<?php

namespace Echoyl\Sa\Http\Middleware;

use App\Helper\ResponseEnum;
use Echoyl\Sa\Services\AdminService;
use Closure;

class AdminAuth
{
    public function handle($request,Closure $next)
    {
        //检测用户是否登录了
        if(!AdminService::checkToken())
        {
            //未登录的情况下返回登录失败
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_EXPIRED;
            return response()->json(['code'=>$code,'msg'=>$msg]);
        }else {
            //var_dump(IlluminateRoute::currentRouteName());exit;
            if(!AdminService::checkAuth())
            {
                return response()->json(['code'=>1,'msg'=>'无操作权限']);
            }
            //添加操作日志
            AdminService::log($request);
            return $next($request);
        }
    }
}
