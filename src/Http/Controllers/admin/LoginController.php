<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\AdminMenuService;
use App\Services\HelperService;
use Echoyl\Sa\Models\perm\PermUser;
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
        $user = PermUser::where(['username'=>$username])->first();
        if($user && $user['password'] == AdminService::pwd($pwd))
        {
            $token = AdminService::login($user);
            $as = new AdminMenuService;
            $avatar = HelperService::uploadParse($user['avatar'], false);
            $avatar = !empty($avatar) ? tomedia($avatar[0]['url']) : '/antadmin/logo.png';
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
            return ['code'=>0,'msg'=>'登录成功，页面跳转中...','data'=>$info,'status'=>0];
        }else
        {
            return ['code'=>1,'msg'=>'登录失败','status'=>1];
        }
        
        
        //session()->save();
        //var_dump(session('username'));exit;
    }

    public function captcha()
    {
        return ['code'=>0,'data'=>CaptchaService::get(request('key'))];
    }

}
