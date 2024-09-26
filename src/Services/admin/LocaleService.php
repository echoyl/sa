<?php

namespace Echoyl\Sa\Services\admin;

use Echoyl\Sa\Models\locale\Category;
use Echoyl\Sa\Models\locale\Config;
use Illuminate\Support\Facades\Schema;

class LocaleService
{
    public static function getModel()
    {
        if(Schema::hasTable('locale_category'))
        {
            return new Category();
        }
        return false;
    }

    public static function enable()
    {
        if(self::getModel())
        {
            return true;
        }else
        {
            return false;
        }
    }

    public static function list()
    {
        $model = self::getModel();
        if(!$model)
        {
            return [];
        }
        $languages = $model->select(['id','title','name'])->where(['state'=>1])->get()->toArray();
        return $languages;
    }
    
    public static function getSetting()
    {
        if(!self::enable())
        {
            return [];
        }

        $all_configs = (new Config())->get()->toArray();
        $languages = self::list();
        $ret = [];
        foreach($languages as $lang)
        {
            $ret[] = [
                'name'=>$lang['name'],
                'title'=>$lang['title'],
                'configs'=>self::formatData($all_configs,$lang['id'])
            ];
        }
        return $ret;
    }

    public static function formatData($all_data,$category_id,$pid = 0,$pre = [])
    {
        $list = collect($all_data);
        $list = $list->where('category_id',$category_id)->where('parent_id',$pid)->sortBy([['displayorder','desc'],['id','asc']])->toArray();
        $ret = [];
        foreach ($list as $val) {
            $child_pre = array_merge($pre,[$val['key']]);
            $children = self::formatData($all_data,$category_id,$val['id'],$child_pre);
            //拼接name 前缀
            $_key = implode('.',$child_pre);
            $ret[$_key]= $val['message'];
            if (!empty($children)) {
                $ret = array_merge($ret,$children);
            }
        }
        return $ret;
    }

    public static function getData($class,$data,$locale)
    {
        $model = new $class;
        if(!$model->locale_columns)
        {
            return $data;
        }

        foreach($model->locale_columns as $column)
        {
            $name = implode('_',[$column,$locale]);
            if(isset($data[$name]) && $data[$name])
            {
                //仅当字段存在且非空的时候才读取该语言字段信息
                $data[$column] = $data[$name];
            }
        }

        return $data;
    }

    public static function search($query,$item,$model,$index = 0)
    {
        $name = $item[0];

        if(!$model || !$model->locale_columns || !in_array($name,$model->locale_columns))
        {
            return $index == 0 ? $query->where([$item]):$query->orWhere([$item]);
        }
        $locales = self::list();
        
        $fn = function($q) use ($locales,$item,$name){
            $q->where([$item]);
            foreach($locales as $lang)
            {
                $new_item = $item;
                $new_item[0] = implode('_',[$name,$lang['name']]);
                $q->orWhere([$new_item]);
            }
        };
        return $index == 0 ? $query->where($fn):$query->orWhere($fn);
    }
}
