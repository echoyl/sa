<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Checkbox extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];
        $val = $val?implode(',',$val):'';
        return $this->getData($val);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $isset = $options['isset'];
        $from = $options['from'];

        if($val && $isset)
        {
            $val = explode(',',$val);
        }else
        {
            $val = '__unset';
        }

        return $this->getData($val,$isset);
    }
}