<?php
namespace Echoyl\Sa\Http\Middleware;

use Echoyl\Sa\Helpers\ResponseEnum;
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
        }
        $user = AdminService::user();
        if($user['state'] != 1 && $user['state'] != 1)
        {
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_PERM;
            return response()->json(['code'=>$code,'msg'=>'该账号已禁用']);
        }
        //添加操作日志
        AdminService::log();
        return $next($request);
    }
}
