<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\CaptchaService;
use Echoyl\Sa\Services\utils\SmsService;

/**
 * @property \Echoyl\Sa\Services\AdminAppService $service
 */
class SmsController extends ApiBaseController
{
    var $max_times = 3;

    public function sms()
    {
        
        $mobile = request('mobile');

        if(!preg_match('/^1\d{10}$/',$mobile))
        {
            return $this->fail([1,'请输入正确手机号码']);
        }

        //验证码检测
        $code = request('captcha.captchaCode');
        $code = $code?:request('vercode');
        $key = request('captcha.captchaKey');
        $key = $key?:request('key');

        if(!CaptchaService::check($key,$code))
        {
            return $this->fail([1,'图形验证码错误']);
        }

        //默认使用阿里云发送信息
        $ss = new SmsService($mobile,$this->max_times);
        [$code,$msg] =  $ss->aliyunSMS($mobile,$this->max_times);

        if($code)
        {
            return $this->fail([$code,$msg]);
        }else
        {
            return $this->success($msg);
        }
    }


}
