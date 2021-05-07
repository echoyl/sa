<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Echoyl\Sa\Models\perm\PermUser;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\CaptchaService;


class LoginController extends Controller
{
    //
    public function index(Request $request)
    {
        //提交登录信息
        $code = $request->input('vercode');

        if(!CaptchaService::check(request('key'),$code))
        {
            return ['code'=>0,'msg'=>'验证码错误','status'=>1];
        }

        $username = $request->input('username');
        $pwd = $request->input('password');
        $user = PermUser::where(['username'=>$username])->first();
        if($user && $user['password'] == AdminService::pwd($pwd))
        {
            $token = AdminService::login($user);
            $info = [
                'id'=>$user['id'],
                'username'=>$user['username'],
                'roleid'=>$user['roleid'],
                'access_token'=>$token,
            ];

            return ['code'=>0,'msg'=>'登录成功','data'=>$info,'status'=>0];
        }else
        {
            return ['code'=>0,'msg'=>'登录失败','status'=>1];
        }
        
        
        //session()->save();
        //var_dump(session('username'));exit;
    }

    public function captcha()
    {
        return ['code'=>0,'data'=>CaptchaService::get(request('key'))];
    }

}
