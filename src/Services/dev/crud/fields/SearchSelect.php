<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;

/**
 * 20240323 前端组件改成了select labelinvalue设置后已不会再将整个数据放入到value中 所以这里要再检测下value字段
 */
class SearchSelect extends BaseField
{
    public function encode($options = [])
    {

        $name = $this->name;

        $data = $this->config['data'];
        $val = $options['val'];
        $isset = $options['isset'];
        $col = $this->config['col'];

        $id_name = $col['value']??'id';

        if($isset && $val)
        {
            if(isset($val[$id_name]))
            {
                $val = $val[$id_name];
            }elseif(isset($val['value']))
            {
                $val = $val['value'];
            }
        }

        $data[$name] = $val;

        return $data;
    }

    public function decode($options = [])
    {
        $data = $this->config['data'];
        $isset = $options['isset'];
        $col = $this->config['col'];
        $id_name = $col['value']??'id';
        $val = $options['val'];
        
        if(isset($col['data_name']) && isset($data[$col['data_name']]) && $isset && $val)
        {
            $d = $data[$col['data_name']];
            if(!$d)
            {
                $val = '__unset';
            }else
            {
                $val = ['label'=>$d[$col['label']??'title']??'','value'=>$d[$id_name]??'',$id_name=>$d[$id_name]??''];
            }
            
        }
        if(!$val || !$isset)
        {
            $val = '__unset';
        }

        return $this->getData($val,$isset);
    }
}