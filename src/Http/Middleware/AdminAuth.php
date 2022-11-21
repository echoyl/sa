<?php
/*
 * @Author: echoyl yliang_1987@126.com
 * @Date: 2022-09-26 11:31:43
 * @LastEditors: echoyl yliang_1987@126.com
 * @LastEditTime: 2022-11-03 14:41:39
 * @FilePath: \donglifengdianchi\vendor\echoyl\sa\src\Http\Middleware\AdminAuth.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

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
        if($user['state'] != 'enable')
        {
            [$code,$msg] = ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_PERM;
            return response()->json(['code'=>$code,'msg'=>'该账号已禁用']);
        }
        //添加操作日志
        AdminService::log($request);
        return $next($request);
    }
}
