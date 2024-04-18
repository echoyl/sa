<?php
namespace Echoyl\Sa\Services\dev\design;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

class BaseService
{
    var $id;
    var $item;
    var $model;
    var $config;
    var $name = 'form_config';
    public function __construct($id = 0)
    {
        
        $name = $this->name;
        $this->id = $id;
        $this->model = new Menu();

        $item = $this->model->where(['id'=>$id])->first();
        if($item)
        {
            $item = $item->toArray();
            $this->item = $item;
            $this->config = isset($item[$name]) && $item[$name]?json_decode($item[$name],true):[];
        }

        return;
    }

    /**
     * 获取上一层 数据信息
     *
     * @param [type] $active
     * @param [type] $datas
     * @return void
     */
    public function getTopData($active,$datas)
    {
        $last_key = array_pop($active);

        if(empty($active))
        {
            return ['',$last_key,$datas];
        }

        $keys = implode('.',$active);

        $top_data = Arr::get($datas,$keys);

        return [$keys,$last_key,$top_data];
    }

    /**
     * 设置数据
     *
     * @param [type] $data 原始数据
     * @param [type] $keys 需要设置的key值如果是空的话直接返回设置的值
     * @param [type] $set_data 设置的值
     * @return void
     */
    public function setData($data,$keys,$set_data)
    {
        if($keys !== '')
        {
            Arr::set($data,$keys, array_values($set_data));
            return $data;
        }else
        {
            return array_values($set_data);
        }
    }

    public function formatTopData($active,$datas)
    {
        array_pop($active);
        
        if(empty($active))
        {
            $datas = array_values($datas);
        }else
        {
            $keys = implode('.',$active);

            $top_data = Arr::get($datas,$keys);
    
            Arr::set($datas,$keys,array_values($top_data));
        }

        return $datas;
    }

    public function differentSort($active,$over,$tabs,$name = 'config')
    {
        $active_data = Arr::get($tabs,implode('.',$active));
        $over[] = $name;
        $keys = implode('.',$over);
        $target = Arr::get($tabs,$keys);
        $target[] = $active_data;
        Arr::set($tabs,$keys,$target);
        //d($tabs,$active);
        //remove the active data
        return $this->removeActive($active,$tabs);
    }

    public function removeActive($active,$tabs)
    {
        [$keys,$last_key,$top_data] = $this->getTopData($active,$tabs);
        unset($top_data[$last_key]);
        if($keys === '')
        {
            //最外层了
            $tabs = $top_data;
        }else
        {
            Arr::set($tabs,$keys,$top_data?array_values($top_data):[]);
        }
        
        return $tabs;
    }
}