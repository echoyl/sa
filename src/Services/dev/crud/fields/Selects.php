<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Illuminate\Support\Arr;

class Selects extends BaseField
{
    public function encode($options = [])
    {
        $val = $options['val'];
        $val = is_array($val)?implode(',',$val):$val;
        return $this->getData($val);
    }

    public function decode($options = [])
    {

        $val = $options['val'];
        $isset = $options['isset'];

        if($val && $isset)
        {
            $val = is_string($val)?explode(',',$val):(is_numeric($val)?[$val]:$val);
            $val = $this->valToInt($val);
        }else
        {
            $val = '__unset';
            
        }

        return $this->getData($val,$isset);
    }
}