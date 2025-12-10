<?php

namespace Echoyl\Sa\Services\dev\utils;

use Symfony\Component\VarExporter\Internal\Exporter;

class Dev
{
    public static function export($data, $indent = 0)
    {
        $indent = $indent > 0 ? str_repeat("\t", $indent) : '';
        $ret = Exporter::export($data, $indent);
        $ret = str_replace("'@php", '', $ret);
        $ret = str_replace("@endphp'", '', $ret);

        return $ret;
    }

    /**
     * 根据换行符排序命名空间
     *
     * @param  array  $spaces  已有的命名空间数组
     * @param  string  $customer  自定义的命名空间字符串通过换行符切割
     * @return void
     */
    public static function sortNamespace($spaces = [], $customer = '')
    {
        // 通过换行符分割字符串
        $customers = $customer ? explode("\n", $customer) : [];
        $spaces = array_unique(array_merge($spaces, $customers));
        sort($spaces);

        return implode("\r", $spaces);
    }
}
