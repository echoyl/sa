<?php
namespace Echoyl\Sa\Services\utils;

use Echoyl\Sa\Models\Smslog;
use Echoyl\Sa\Services\AliyunService;
use Echoyl\Sa\Services\SetsService;
use Illuminate\Support\Arr;

class SmsService
{
    /**
     * @var int 每天最大发送量
     */
    var $max_times = 0;

    /**
     * @var string 手机号码
     */
    var $mobile;

    public function __construct($mobile,$max_times = 3)
    {
        $this->max_times = $max_times;
        $this->mobile = $mobile;
    }

    public function getCode()
    {
        $model = new Smslog();
        $max = $this->max_times;
        $mobile = $this->mobile;
        //读取用户今天已经发了几次短信了
        $count = $model->where('mobile',$mobile)->whereBetween('created_at',[date("Y-m-d"),date("Y-m-d").' 23:59:59'])->count();
        if($count >= $max)
        {
            return [1,'每个号码一天最多发送'.$max.'次验证码'];
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
        return [0,$code];
    }

    public function checkCode($code)
    {
        $mobile = $this->mobile;

        if($code == 6666 && env('APP_ENV') == 'local')
        {
            return true;
        }
        $model = new Smslog();
        $data = $model->where('mobile',$mobile)->orderBy('id','desc')->first();

        if(!$data || $data['status'] == 1 || $data['code'] != $code || strtotime($data['created_at']) + 1800 < time())
        {
            return false;
        }else
        {
            $model->where('id',$data['id'])->update(['status'=>1]);
            return true;
        }
        
    }

    public function aliyunSMS()
    {

        $model = new Smslog();
        
        [$code,$data] = $this->getCode();

        if($code)
        {
            return [$code,$data];
        }

        $code = $data;
        $mobile = $this->mobile;

        $ss = new SetsService();
        $setting = $ss->get('setting');
        $code_id = Arr::get($setting,'sms_code_id');
        $sms_name = Arr::get($setting,'sms_name');

        if(!$code_id || !$sms_name)
        {
            return [1,'短信配置错误'];
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
            return [0,'发送成功'];
        }else
		{
            return [$retcode,$msg];
		}
    }
}