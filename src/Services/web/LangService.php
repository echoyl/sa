<?php

namespace Echoyl\Sa\Services\web;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;

class LangService
{
    public static function getLang()
    {
        return App::getLocale();
    }

    public static function getVal($val, $name)
    {
        $locale = self::getLang();

        $key = implode('_', [$name, $locale]);

        $v = Arr::get($val, $key);

        return $v ?: Arr::get($val, $name);
    }
}
