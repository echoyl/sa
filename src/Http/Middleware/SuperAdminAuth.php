<?php

namespace Echoyl\Sa\Http\Middleware;

use Closure;
use Echoyl\Sa\Services\AdminService;

class SuperAdminAuth
{
    public function handle($request, Closure $next)
    {
        $user = AdminService::user();

        if (! $user || ! AdminService::isSuper($user)) {
            return response()->json(['code' => 1, 'msg' => '该账号角色非超级管理员']);
        }

        return $next($request);
    }
}
