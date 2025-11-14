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
}
