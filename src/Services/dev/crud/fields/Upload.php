<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\HelperService;

class Upload extends BaseField
{
    public $tmp_prefix = 'tmp/';

    public $storage_prefix = 'app/public/';

    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $origin_val = $options['origin_val']; // 原数据，先进行对比后如果不一样需要删除原文件
        $isset = $options['isset'];
        $from = $options['from'] ?? 'update';

        if ($from == 'delete') {
            $isset = true;
        }

        if (empty($val) && ! $isset) {

        } else {
            $val = $this->diffFileVal($val, $origin_val);

            $data[$name] = HelperService::uploadParse($val ?? '', true);
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $val = $options['val'];
        $name = $this->col['name'];
        $type = $options['type'];
        $isset = $options['isset'];
        if ($val) {
            $par = $type == 'image' ? ['p' => 's'] : [];
            $data[$name] = HelperService::uploadParse($val ?? '', false, $par);
        } else {
            if ($isset) {
                unset($data[$name]);
            }
        }

        return $data;
    }
}
