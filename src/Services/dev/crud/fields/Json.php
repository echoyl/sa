<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;


class Json extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        
        if(empty($val))
        {
            $val = '';
        }else
        {
            if(!is_string($val))
            {
                $val = json_encode($val);
            }else
            {
                if($val == '{}')
                {
                    $val = '';
                }
            }
        }

        $data[$name] = $val;

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $val = $options['val'];
        if($val)
        {  
            if($val == '{}')
            {
                $val = '__unset';
            }else
            {
                $val = is_string($val)?json_decode($val,true):$val;
            }
        }else{
            $val = '__unset';
        }

        $name = $this->col['name'];

        if($val == '__unset')
        {
            if($options['isset'])
            {
                unset($data[$name]);
            }
        }else
        {
            $data[$name] = $val;
        }

        return $data;
    }
}