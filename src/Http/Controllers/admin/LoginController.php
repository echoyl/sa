<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\CaptchaService;

class LoginController extends Controller
{
    //
    public function index(Request $request)
    {
        //提交登录信息
        $code = $request->input('captcha.captchaCode');
        $code = $code?:$request->input('vercode');
        $key = $request->input('captcha.captchaKey');
        $key = $key?:$request->input('key');

        if(!CaptchaService::check($key,$code))
        {
            return ['code'=>1,'msg'=>'验证码错误','status'=>1];
        }

        $username = $request->input('username');
        $pwd = $request->input('password');

        $info = AdminService::doLogin($username,$pwd);

        if($info)
        {
            return ['code'=>0,'msg'=>'登录成功，页面跳转中...','data'=>$info,'status'=>0];
        }else
        {
            return ['code'=>1,'msg'=>'账号或密码错误，请重新输入','status'=>1];
        }
    }

    public function captcha()
    {
        return ['code'=>0,'data'=>CaptchaService::get(request('key'))];
    }

}
