<?php

namespace Echoyl\Sa\Http\Middleware;

use Closure;
use Echoyl\Sa\Services\admin\LocaleService;

class RememberToken
{
    public function handle($request, Closure $next)
    {
        LocaleService::setLang();
        $remember = request()->header('Sa-Remember');
        $expiration = config('sanctum.expiration', 60 * 12 * 2); // 默认一天有效期
        $expiration = $remember ? config('sanctum.expiration_remember', 60 * 12 * 6) : $expiration;

        config(['sanctum.expiration' => $expiration]);

        return $next($request);
    }
}
