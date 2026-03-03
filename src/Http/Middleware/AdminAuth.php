<?php

namespace Echoyl\Sa\Http\Middleware;

use Closure;
use Echoyl\Sa\Helpers\ResponseEnum;
use Echoyl\Sa\Services\AdminService;
use Illuminate\Support\Facades\Lang;

class AdminAuth
{
    public function handle($request, Closure $next)
    {
        // 检测用户是否登录了
        // d(AdminService::user());
        if (! AdminService::checkToken()) {
            // 未登录的情况下返回登录失败
            $key = 'sa::response.expired';
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_EXPIRED;
            $msg = Lang::has($key) ? __($key) : $msg;

            return response()->json(['code' => $code, 'msg' => $msg]);
        }
        $user = AdminService::user();
        if ($user['state'] != 1) {
            $key = 'sa::response.nopermission';
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_EXPIRED;
            $msg = Lang::has($key) ? __($key) : $msg;

            return response()->json(['code' => $code, 'msg' => $msg]);
        }
        // 添加操作日志
        AdminService::log();

        return $next($request);
    }
}
