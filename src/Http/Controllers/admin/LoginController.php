<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\HelperService;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\CaptchaService;
use Echoyl\Sa\Services\dev\MenuService;

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
        $user = User::where(['username'=>$username])->first();
        if($user && $user['password'] == AdminService::pwd($pwd))
        {
            $token = AdminService::login($user);
            $as = new MenuService;
            $avatar = HelperService::uploadParse($user['avatar'], false);
            $avatar = !empty($avatar) ? tomedia($avatar[0]['url']) : '';
            $info = [
                'userinfo'=>[
                    'id'=>$user['id'],
                    'username'=>$user['username'],
                    'roleid'=>$user['roleid'],
                    'name' => $user['username'],
                    'avatar' => $avatar,
                    'permission' => $user['perms2'],
                    'menuData'=>$as->get(),
                ],
                'access_token'=>$token,
            ];
            AdminService::log($request,'登录',['id'=>$user['id']]);
            //更新最后登录时间
            User::where(['id'=>$user['id']])->update(['latest_login_at'=>now()]);
            return ['code'=>0,'msg'=>'登录成功，页面跳转中...','data'=>$info,'status'=>0];
        }else
        {
            return ['code'=>1,'msg'=>'账号或密码错误，请重新输入','status'=>1];
        }
        
        
        //session()->save();
        //var_dump(session('username'));exit;
    }

    public function captcha()
    {
        return ['code'=>0,'data'=>CaptchaService::get(request('key'))];
    }

}
