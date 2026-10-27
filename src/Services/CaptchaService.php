<?php

namespace Echoyl\Sa\Services;

use Illuminate\Support\Facades\Cache;

class CaptchaService
{
    // cache key 命名空间，避免与其它业务的 cache key 混淆
    // 注意：mews/captcha 内部用 'captcha_'.md5($key) 存验证码答案，前缀不能与其相同，否则会覆盖答案导致校验必失败
    private const CACHE_PREFIX = 'sa_captcha_';

    public static function get($key = '')
    {
        $captcha = app('captcha')->create('flat', true);

        // 改变session存储方式 存到cache中
        if ($key && self::isValidKey($key)) {
            Cache::forget(self::CACHE_PREFIX.$key);
        }

        Cache::put(self::CACHE_PREFIX.md5($captcha['key']), $captcha['key'], now()->addMinute(2)); // 2分钟有效期
        // $captcha['key_back'] = $captcha['key'];
        $captcha['key'] = md5($captcha['key']);

        return $captcha;
    }

    public static function check($key, $value)
    {
        // 只接受本服务签发的key，防止客户端传入其它业务的cache key造成误删
        if (! self::isValidKey($key)) {
            return false;
        }

        $cache_key = self::CACHE_PREFIX.$key;

        // 清理过期cache
        $cache = Cache::get($cache_key);
        Cache::forget($cache_key);

        if ($cache && app('captcha')->check_api($value, $cache)) {
            return true;
        }

        return false;
    }

    /**
     * 验证码key由 get() 返回，固定为32位md5字符串
     */
    private static function isValidKey($key)
    {
        return is_string($key) && preg_match('/^[0-9a-f]{32}$/', $key) === 1;
    }
}
