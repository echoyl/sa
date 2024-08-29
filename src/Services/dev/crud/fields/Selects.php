<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

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
            $val = is_string($val)?explode(',',$val):$val;
            // foreach($val as $k=>$v)
            // {
            //     if(is_numeric($v))
            //     {
            //         $val[$k] = intval($v);
            //     }
            // }
        }else
        {
            $val = '__unset';
            
        }

        return $this->getData($val,$isset);
    }
}