<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class With extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $isset = $options['isset'];

        if ($isset && $val) {
            foreach ($val as $k => $v) {
                $new_key = implode('_', [$name, $k]);
                $data[$new_key] = $v;
            }
        }

        return $data;
    }

    public function decode($options = [])
    {
        return $this->encode($options);
    }
}
