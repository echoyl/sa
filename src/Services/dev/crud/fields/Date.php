<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Date extends BaseField
{
    public function encode($options = [])
    {

        return $this->decode($options);
    }

    public function decode($options = [])
    {
        $val = $options['val'];
        $isset = $options['isset'];
        if (! $val) {
            $val = '__unset';
        }

        return $this->getData($val, $isset);
    }
}
