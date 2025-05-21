<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\dev\crud\ParseData;
use Illuminate\Support\Arr;

class ModalSelects extends BaseField
{
    public function encode($options = [])
    {

        $val = $options['val'];
        $col = $this->config['col'];
        $isset = $options['isset'];
        $id_name = $col['value']??'id';
        if($isset && $val && is_array($val))
        {
            //如果传输的数据是数组 这里暂时数据库中只存储逗号拼接的id值  如果之后需要关联模型处理再说
            $_v = [];
            foreach($val as $v)
            {
                if(isset($v['data']) && isset($v['data'][$id_name]))
                {
                    $_v[] = $v['data'][$id_name];
                }
            }
            if(!empty($_v))
            {
                $val = implode(',',$_v);
            }else
            {
                $val = '';
            }
        }

        return $this->getData($val);
    }

    public function decode($options = [])
    {
        $isset = $options['isset'];
        $col = $this->config['col'];
        $id_name = $col['value']??'id';
        $vals = $options['val'];
        $from = $options['from']??'';//只在详情时解析数据
        $class = Arr::get($col,'class');
        $data_name = Arr::get($col,'data_name');
        $name = $this->name;

        if($vals && $isset && $from == 'detail' && $class)
        {
            $val = null;
            $ids = explode(',',$vals);
            $classins = new $class;
            $datas = $classins->whereIn($id_name,$ids)->orderByRaw('FIELD('.$id_name.', ' . $vals . ')')->get()->toArray();
            foreach($datas as $d)
            {
                //再次处理数据
                $ps = new ParseData($class);
                $ps->make($d,'decode',$from,99);//只解析一层
                $val[] = ['id'=>0,'data'=>$d];
            }
            if(!$val || !$isset)
            {
                $val = '__unset';
            }

            $data = $this->getData($val,$isset);
            if($data_name && isset($data[$name]))
            {
                $data[$data_name] = $data[$name];
                $data[$name] = $vals;
            }
            return $data;
        }else
        {
            return $this->config['data'];
        }
    }

}