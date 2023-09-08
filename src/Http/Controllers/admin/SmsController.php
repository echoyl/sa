<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Models\Smslog;
use Echoyl\Sa\Services\AliyunService;
use Echoyl\Sa\Services\CaptchaService;
use Echoyl\Sa\Services\SetsService;
use Illuminate\Support\Arr;

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
        return $this->aliyunSMS($mobile,$this->max_times);
    }


    public function aliyunSMS($mobile,$max)
    {

        $model = new Smslog();
        //读取用户今天已经发了几次短信了
        $count = $model->where('mobile',$mobile)->whereBetween('created_at',[date("Y-m-d"),date("Y-m-d").' 23:59:59'])->count();
        if($count >= $max)
        {
            return $this->fail([1,'每个号码一天最多发送'.$max.'次验证码']);
        }
        //读取上一次的短信看看是否过期了
        $last_sms = $model->where(['mobile'=>$mobile,'status'=>0])->orderBy('id','desc')->first();
        if($last_sms && strtotime($last_sms['created_at']) + 1800 > time())
        {
            $code = $last_sms['code'];
        }else
        {
            $code = rand(1000,9999);
        }
        $ss = new SetsService();
        $setting = $ss->get('setting');
        $code_id = Arr::get($setting,'sms_code_id');
        $sms_name = Arr::get($setting,'sms_name');

        if(!$code_id || !$sms_name)
        {
            return $this->fail([1,'短信配置错误']);
        }

        $result = AliyunService::sendSMS($mobile,['code'=>$code],['id'=>$code_id,'name'=>$sms_name]);

        $retcode = $result['code'];
		$msg = $result['msg'];
        if(!$retcode)
        {
            $data = [
                'mobile'=>$mobile,
                'code'=>$code,
                'created_at'=>date("Y-m-d H:i:s"),
            ];
            $model->insert($data);
			return $this->success('发送成功');
        }else
		{
			return $this->fail([$retcode,$msg]);
		}
    }

}
