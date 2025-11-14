<?php

namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

/**
 * 已废弃
 */
class State extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];

        if ($val == 1 || $val == 'enable') {
            $val = 'enable';
        } else {
            $val = 'disable';
        }

        return $this->getData($val);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $from = $options['from'];
        if ($from == 'detail') {
            $val = $val == 'enable' ? true : false;
        }

        return $this->getData($val);
    }
}
