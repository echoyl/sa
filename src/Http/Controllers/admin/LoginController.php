<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\CaptchaService;
use Echoyl\Sa\Services\utils\SmsService;
use Illuminate\Http\Request;

/**
 * @property \Echoyl\Sa\Services\AdminAppService $service
 */
class LoginController extends ApiBaseController
{
    //
    public function index(Request $request)
    {
        // 提交登录信息
        $loginType = request('loginType');

        $code = $request->input('captcha.captchaCode');
        $code = $code ?: $request->input('vercode');
        $key = $request->input('captcha.captchaKey');
        $key = $key ?: $request->input('key');
        $as = new AdminService;

        if (! $as->loginCheck()) {
            if (! CaptchaService::check($key, $code)) {
                return $this->fail([2, '图形验证码错误']);
            }
        }

        if ($loginType == 'phone') {
            // 手机号码登录
            $code = request('mobilecode');
            $mobile = request('mobile');
            if (! $code || ! $mobile) {
                return $this->fail([1, '请输入手机验证码']);
            }
            $ss = new SmsService($mobile);
            if (! $ss->checkCode($code)) {
                $as->loginErrorLog();

                return $this->fail([$as->loginCheck() ? 1 : 3, '手机验证码错误']);
            }

            // 验证码正确 登录账号
            $info = AdminService::doLoginByMobile($mobile);
        } else {
            $username = $request->input('username');
            $pwd = $request->input('password');

            $info = AdminService::doLoginByUsername($username, $pwd);
        }

        if ($info) {
            $info['userinfo'] = $this->service->parseUserInfo($info['userinfo'], $info['user']);
            unset($info['user']);
            $as->loginErrorLog('clear');
            $this->service->triggerLogin($info);

            return $this->success($info, [0, '登录成功，页面跳转中...']);
        } else {
            $as->loginErrorLog();

            return $this->fail([$as->loginCheck() ? 1 : 3, '账号或密码错误，请重新输入']);
        }
    }

    public function captcha()
    {
        return $this->success(CaptchaService::get(request('key')));
    }
}
