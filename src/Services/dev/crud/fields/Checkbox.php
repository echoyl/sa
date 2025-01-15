<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Illuminate\Support\Arr;

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

        if($val)
        {
            //未设置，但是有默认值也需要处理下
            $val = is_string($val)?explode(',',$val):(is_numeric($val)?[$val]:$val);
            $val = $this->valToInt($val);
        }else
        {
            $val = '__unset';
        }

        return $this->getData($val,$isset);
    }
}