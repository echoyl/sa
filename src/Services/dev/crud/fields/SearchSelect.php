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

        if($val && $isset)
        {
            //未设置with数据，这个时候我们手动组合成值的结构，不然当选项中不存在该值时前端会报错
            $val = ['value'=>$val,$id_name=>$val];
            if(isset($col['data_name']) && isset($data[$col['data_name']]))
            {
                $d = $data[$col['data_name']];
                if($d)
                {
                    $label = $d[$col['label']]??($d['title']??'');
                    $val = array_merge($d,['label'=>$label,'value'=>$d[$id_name]??'',$id_name=>$d[$id_name]??'']);
                }
            }
        }
        
        if(!$val || !$isset)
        {
            $val = '__unset';
        }

        return $this->getData($val,$isset);
    }
}