<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Radio extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];
        $val = is_numeric($val) ? intval($val) : $val;

        return $this->getData($val);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $isset = $options['isset'];

        // radio不同于select 可以支持值为0，因为select如果传值为0的话就会显示在input中
        if ($isset) {
            $val = is_numeric($val) ? intval($val) : $val;
        } else {
            $val = '__unset';
        }

        return $this->getData($val, $isset);
    }
}
