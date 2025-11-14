<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Illuminate\Support\Arr;

class Slider extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $isset = $options['isset'];

        if ($isset) {
            $min = Arr::get($val, 0, '');
            $max = Arr::get($val, 1, '');
            $min_name = implode('_', [$name, 'min']);
            $max_name = implode('_', [$name, 'max']);
            $data[$min_name] = is_null($min) ? '' : $min;
            $data[$max_name] = is_null($max) ? '' : $max;

            // 如果用了其它字段需要将该字段移除
            $val = '__unset';
            Arr::forget($data, $name);
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $name = $this->name;

        $min_name = implode('_', [$name, 'min']);
        $max_name = implode('_', [$name, 'max']);
        if (isset($data[$min_name]) && isset($data[$max_name])) {
            $data[$name] = [$data[$min_name], $data[$max_name]];
        }

        return $data;
    }
}
