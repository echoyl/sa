<?php
namespace Echoyl\Sa\Services\dev\crud\fields;

use Echoyl\Sa\Models\Pca as ModelsPca;
use Echoyl\Sa\Services\dev\crud\BaseField;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class Pca extends BaseField
{
    var $config;
    var $keys = ['province','city','area'];
    var $level = 3;
    var $col;
    public function __construct($config)
    {
        parent::__construct($config);

        $col = $this->col;

        $this->level = Arr::get($col,'level',3);//省市区列数
        $topCode = Arr::get($col,'topCode','');//省市区指定上级
        $topLevel = $topCode?count(explode(',',$topCode)):0;
        $topLevel = $topLevel > 3?3:$topLevel;
        $keys = $this->keys;
        for($i=0;$i<$topLevel;$i++)
        {
            //如果有上级 那么将多余name弹出
            array_shift($keys);
        }
        $this->keys = $keys;
    }

    public function encode($options = [])
    {
        $name = $this->col['name'];
        $isset = $options['isset'];
        $val = $options['val'];
        $data = $this->config['data'];
        $need_set = true;

        if($isset && $val != '__unset')
        {
            $keys = $this->keys;
            foreach($keys as $k=>$v)
            {
                if(!isset($val[$k]) || $k >= $this->level)
                {
                    continue;
                }
                $data[$v] = $val[$k];
            }
            if(!in_array($name,$keys))
            {
                //如果用了其它字段需要将该字段移除
                $val = '__unset';
            }else
            {
                $need_set = false;//字段重复不需要再设值了
            }
        }
        if($val === '__unset')
        {
            if(isset($data[$name]))
            {
                unset($data[$name]);
            }
        }else
        {
            if($need_set)
            {
                $data[$name] = $val;
            }
        }
        return $data;
    }

    public function decode($options = [])
    {
        $val = [];
        $keys = $this->keys;
        $data = $this->config['data'];
        $isset = $options['isset'];
        if(!$isset)
        {
            return $data;
        }
        foreach($keys as $k=>$v)
        {
            if(isset($data[$v]) && $data[$v] && $k < $this->level)
            {
                $val[] = $data[$v];
            }
        }
        $this->name = $this->col['name'];
        return $this->getData($val,$isset);
    }

    public function search($m,$options = [])
    {
        $search_val = $options['search_val'];

        $json = HelperService::json_validate($search_val);
        if($json)
        {
            $search_val = $json;
        }else
        {
            $search_val = [$search_val];
        }

        foreach($this->keys as $k=>$v)
        {
            if(isset($search_val[$k]))
            {
                $m = $m->where($v,$search_val[$k]);
            }
        }

        return $m;
    }

    /**
     * 自动解析数据中的省市区
     *
     * @param string $split
     * @return void
     */
    public function decodeStr($split = ' / ')
    {
        $all = self::allPluckData();
        $data = $this->config['data'];
        $strs = [];
        foreach($this->keys as $key)
        {
            if(!isset($data[$key]))
            {
                continue;
            }
            if(isset($all[$data[$key]]))
            {
                $strs[] = $all[$data[$key]];
            }
        }
        return implode($split,$strs);
    }

    public static function allPluckData()
    {
        static $data = [];
        $data = ModelsPca::pluck('name','code');
        return $data;
    }
}