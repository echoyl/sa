<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

class Cascader extends BaseField
{
    public function encode($options = [])
    {
        $name = $this->name;
        $val = $options['val'];
        $_name = '_' . $name;

        if (!empty($val)) 
        {
            if(is_numeric($val))
            {
                //检测数据类型
                $val = [$val];
            }
            $this->config['data'][$_name] = json_encode($val);
            $__val = [];
            $val_len = count($val);
            foreach ($val as $_key => $_val) 
            {
                if (is_numeric($_val)) 
                {
                    if ($_key == $val_len - 1) 
                    {
                        $__val[] = $_val;
                    }
                } elseif (is_array($_val)) 
                {
                    $__val[] = array_pop($_val);
                }
            }
            $val = implode(',', $__val);
        } else 
        {
            $val = 0;
            $this->config['data'][$_name] = '';
        }

        return $this->getData($val);
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $name = $this->name;
        $val = $options['val'];
        $_name = '_' . $name;

        $val = isset($data[$_name]) && $data[$_name] ? json_decode($data[$_name], true) : '';
        return $this->getData($val);
    }
}