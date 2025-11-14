<?php

namespace Echoyl\Sa\Http\Middleware;

use Closure;

class RememberToken
{
    public function handle($request, Closure $next)
    {

        $remember = request()->header('Sa-Remember');
        if ($remember) {
            // 记住登录 设置有效期 3天
            config(['sanctum.expiration' => config('sanctum.expiration_remember', 60 * 12 * 6)]);
        }

        return $next($request);
    }
}
