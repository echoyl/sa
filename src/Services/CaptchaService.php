<?php
namespace Echoyl\Sa\Services;
use Illuminate\Support\Facades\Cache;

class CaptchaService
{
    public static function get($key = '')
    {
        $captcha = app('captcha')->create('flat',true);

        //改变session存储方式 存到cache中
        if($key)
        {
            Cache::forget(md5($key));
        }
      	
        Cache::put(md5($captcha['key']),$captcha['key'],now()->addMinute(2));//2分钟有效期
      	//$captcha['key_back'] = $captcha['key'];
      	$captcha['key'] = md5($captcha['key']);
      	
        return $captcha;
    }

    public static function check($key,$value)
    {
        //清理过期cache

        $cache = Cache::get($key);
        Cache::forget($key);
        if($cache && app('captcha')->check_api($value,$cache))
        {
            return true;
        }
        return false;
    }
}
