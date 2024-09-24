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
}
