<?php
namespace Echoyl\Sa\Services\dev\crud\item;

use Echoyl\Sa\Services\dev\crud\CrudInterface;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class Pca implements CrudInterface
{
    var $config;
    var $keys = ['province','city','area'];
    var $level = 3;
    var $col;
    public function __construct($config)
    {
        $this->config = $config;

        $this->col = $this->config['col'];

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

        if($isset)
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
            unset($data[$name]);
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
        foreach($keys as $k=>$v)
        {
            if(isset($data[$v]) && $data[$v] && $k < $this->level)
            {
                $val[] = $data[$v];
            }
        }
        $name = $this->col['name'];
        $data[$name] = $val;
        return $data;
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
}