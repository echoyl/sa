<?php
namespace Echoyl\Sa\Services\dev\crud;

use Echoyl\Sa\Services\dev\crud\CrudInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class BaseField implements CrudInterface
{
    var $config;
    var $col;
    var $name;
    var $tmp_prefix = 'tmp/';
    public function __construct($config)
    {
        $this->config = $config;

        $this->col = $this->config['col'];

        $this->name = $this->col['name'];

    }

    public function encode($options = [])
    {

        $val = $options['val'];

        $data = $this->getData($val);

        // if($options['type'] == 'switch' && $this->name == 'is_recommend')
        // {
        //     d($val,$this->name,$this->config['data'],$data);
        // }

        return $data;
    }

    public function decode($options = [])
    {

        $val = $options['val'];

        return $this->getData($val);
    }

    public function search($m,$options = [])
    {
        return $m;
    }

    public function moveFile($value)
    {
        if(strpos($value,$this->tmp_prefix) === 0)
        {
            //将文件转移
            $new_value = str_replace($this->tmp_prefix,'',$value);
            //d(storage_path($this->storage_prefix.$value),storage_path($this->storage_prefix.$new_value));
            Storage::move($value,$new_value);
            return $new_value;
        }
        return $value;
    }

    public function diffFileVal($data,$origin_data)
    {
        $new_values = [];
        if (is_array($data) && !empty($data)) {
            foreach($data as $key=>$item)
            {
                $value = Arr::get($item,'value');
                if(!$value)
                {
                    continue;
                }
                $new_values[] = $value;
                //检测是否时tmp 开头
                $data[$key]['value'] = $this->moveFile($value);
            }
        }
        $origin_data = is_string($origin_data)?json_decode($origin_data,true):$origin_data;

        if (is_array($origin_data) && !empty($origin_data)) {
            foreach($origin_data as $item)
            {
                $value = Arr::get($item,'value');
                if(!$value)
                {
                    continue;
                }
                if(!in_array($value,$new_values))
                {
                    //旧文件删除
                    Storage::delete($value);
                }
            }
        }
        return $data;
    }

    public function getData($val,$isset = true)
    {
        $data = $this->config['data'];
        $name = $this->name;

        if($val === '__unset')
        {
            if($isset)
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