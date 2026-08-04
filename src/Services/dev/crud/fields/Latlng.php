<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Illuminate\Support\Arr;

class Latlng extends BaseField
{
    public function getFieldName()
    {
        $col = $this->col;
        // 获取配置中的字段名称 默认值为lat lng
        $lat_name = Arr::get($col, 'setting.lat', 'lat');
        $lng_name = Arr::get($col, 'setting.lng', 'lng');

        return [$lat_name, $lng_name];
    }

    public function encode($options = [])
    {
        $name = $this->name;
        [$lat_name, $lng_name] = $this->getFieldName();
        $data = $this->config['data'];
        $val = $options['val'];
        $isset = $options['isset'];

        if ($isset) {
            $lat = Arr::get($val, 0, '');
            $lng = Arr::get($val, 1, '');
            $data[$lat_name] = is_null($lat) ? '' : $lat;
            $data[$lng_name] = is_null($lng) ? '' : $lng;

            if (! in_array($name, [$lat_name, $lng_name])) {
                // 如果用了其它字段需要将该字段移除
                $val = '__unset';
                unset($data[$name]);
            }
        }

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $name = $this->name;

        [$lat_name, $lng_name] = $this->getFieldName();

        if (isset($data[$lat_name]) && $data[$lng_name]) {
            $data[$name] = [$data[$lat_name], $data[$lng_name]];
        }

        return $data;
    }
}
