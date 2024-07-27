<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;


class Switchstate extends BaseField
{

    public function decode($options = [])
    {

        $val = $options['val'];
        $from = $options['from'];
        $isset = $options['isset'];
        if ($from == 'list' && !$isset) {
            $val = '__unset';
        }
        
        return $this->getData($val);
    }
}