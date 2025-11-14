<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Illuminate\Support\Arr;

class Price extends BaseField
{
    public $precision = [
        'price' => 100, // 价格2位小数
        'mprice' => 1000, // 三位小数
        'mmprice' => 10000, // 四位小数
    ];

    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $isset = Arr::get($options, 'isset', false);
        $type = Arr::get($options, 'type', 'price');

        $weight = $this->precision[$type] ?? $this->precision['price'];

        if ($isset) {
            $val = bcmul($val, $weight);
            $data[$name] = $val;
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $val = $options['val'];
        $isset = Arr::get($options, 'isset', false);
        $name = $this->name;
        $type = Arr::get($options, 'type', 'price');
        $weight = $this->precision[$type] ?? $this->precision['price'];

        if ($isset) {
            $val = floatval($val / $weight);
            $data[$name] = $val;
        }

        return $data;
    }
}
