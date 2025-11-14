<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\dev\crud\ParseData;
use Illuminate\Support\Arr;

class SearchSelects extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $vals = $options['val'];
        $isset = $options['isset'];
        $col = $this->config['col'];

        $id_name = $col['value'] ?? 'id';
        $_vals = [];

        if ($isset && is_array($vals)) {
            foreach ($vals as $val) {
                if (isset($val[$id_name])) {
                    $_vals[] = $val[$id_name];

                } elseif (isset($val['value'])) {
                    $_vals[] = $val['value'];
                }
            }
        }

        $data[$name] = implode(',', $_vals);

        return $data;
    }

    public function decode($options = [])
    {
        $isset = $options['isset'];
        $col = $this->config['col'];
        $id_name = $col['value'] ?? 'id';
        $vals = $options['val'];
        $from = $options['from'] ?? ''; // 只在详情时解析数据
        $class = Arr::get($col, 'class');

        if ($vals && $isset && $from == 'detail' && $class) {
            $val = null;
            $ids = explode(',', $vals);
            $classins = new $class;
            $datas = $classins->whereIn($id_name, $ids)->get()->toArray();
            foreach ($datas as $d) {
                // 再次处理数据
                $ps = new ParseData($class);
                $ps->make($d, 'decode', $from, 99); // 只解析一层
                $val[] = array_merge($d, ['value' => $d[$id_name] ?? '', $id_name => $d[$id_name] ?? '']);
            }
            if (! $val || ! $isset) {
                $val = '__unset';
            }

            return $this->getData($val, $isset);
        } else {
            return $this->config['data'];
        }
    }
}
