<?php
namespace Echoyl\Sa\Services\dev\utils;
class Utils
{

    public static $value_type_map = [
        'select'=>'select',
        'selects'=>'select',
        'search_select'=>'debounceSelect',
        'textarea'=>'textarea',
        'image'=>'uploader',
        'datetime'=>'dateTime',
        'switch'=>'switch',
        'cascader'=>'cascader',
        'cascaders'=>'cascader',
        'pca'=>'pca',
        'tmapInput'=>'tmapInput',
        'tinyEditor'=>'tinyEditor',
        'price'=>'digit',
        'confirm'=>'confirm'
    ];

    public static $title_arr = [
        'created_at'=>'创建时间',
        'updated_at'=>'最后更新时间'
    ];

    public static function uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    public static function getPath($val, $menus,$field = 'name')
    {
        $alias = [$val[$field]];
        //d($parent);
        if ($val['parent_id']) {
            $parent = collect($menus)->filter(function ($item) use ($val) {
                return $item['id'] === $val['parent_id'];
            })->first();
            //$alias[] = $parent['alias'];
            $alias = array_merge($alias, self::getPath($parent, $menus,$field));
        }

        return $alias;

    }

    public static function arrGet($arr,$key,$value)
    {
        if(!$arr)return false;
        return collect($arr)->first(function($item) use($value,$key){
            return $item[$key] == $value;
        });
    }
}