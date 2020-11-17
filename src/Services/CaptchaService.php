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
            Cache::forget($key);
        }
        Cache::put($captcha['key'],1,now()->addMinute(2));//2分钟有效期
        return $captcha;
    }

    public static function check($key,$value)
    {
        //清理过期cache


        $cache = Cache::get($key);
        Cache::forget($key);
        //d([$cache,$key,$value]);
        if($cache && app('captcha')->check_api($value,$key))
        {
            
            return true;
        }
        return false;
    }






}
