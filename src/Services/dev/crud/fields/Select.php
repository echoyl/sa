<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Select extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];
        $val = is_numeric($val)?intval($val):$val;
        return $this->getData($val);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $isset = $options['isset'];
        $from = $options['from'];

        if($val && $isset)
        {
            $val = is_numeric($val)?intval($val):$val;
        }else
        {
            $val = '__unset';
        }

        return $this->getData($val,$isset);
    }
}