<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Model;

class Utils
{

    public static $value_type_map = [
        'select'=>'select',
        'selects'=>'select',
        'search_select'=>'debounceSelect',
        'textarea'=>'textarea',
        'image'=>'uploader',
        'datetime'=>'dateTime',
        'date'=>'date',
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
        'updated_at'=>'最后更新时间',
        'displayorder'=>'排序权重',
    ];

    public static function uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    public static function getPath($val, $menus,$field = 'name')
    {
        if(!$val)
        {
            return [];
        }
        $alias = isset($val[$field])?[$val[$field]]:[];
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

    public static function toTree($fields)
    {
        $data = ['columns'=>[],'models'=>[]];
        foreach($fields as $field)
        {
            //检测字段是数字，外联模型，字段名称
            $length = count($field);
            if($length == 1)
            {
                //当前模型字段
                $data['columns'][] = $field[0];
            }elseif($length == 2)
            {
                if(!isset($data['models'][$field[0]]))
                {
                    $data['models'][$field[0]] = ['columns'=>[],'models'=>[]];
                }
                if($field[1])
                {
                    $data['models'][$field[0]]['columns'][] = $field[1];
                }
            }elseif($length > 2)
            {
                $first_field = array_shift($field);
                if(!isset($data['models'][$first_field]))
                {
                    $data['models'][$first_field] = ['columns'=>[],'models'=>[]];
                }
                $data['models'][$first_field] = array_merge($data['models'][$first_field],self::toTree([$field]));
            }
        }
        return $data;
    }
    public static function withTree($trees,$indent = 0,$i = 0)
    {
        if(empty($trees))
        {
            return '';
        }
        $data = [];
        $replace = [];
        $search = [];
        $j = 0;
        foreach($trees as $name=>$tree)
        {
            if(is_numeric($name))
            {
                $model = (new Model())->where(['id'=>$name])->first();
                if(!$model)
                {
                    return '';
                }
                $name = $model['name'];
            }
            if(!empty($tree['models']))
            {
                $replace[] = self::withTree($tree['models'],$indent + 1,$i+1);
                $sear = 'sear_'.$j;
                $search[] = $sear;
                $inner_with = '->with('.$sear.')';
                // d($inner_with);
            }else
            {
                $inner_with = '';
            }
            if(!empty($tree['columns']))
            {
                $data[$name] = '@phpfunction($q'.$i.'){$q'.$i.'->select('.json_encode($tree['columns']).')'.$inner_with.';}@endphp';
            }else
            {
                if($inner_with)
                {
                    $data[$name] = '@phpfunction($q'.$i.'){$q'.$i.$inner_with.';}@endphp';
                }else
                {
                    $data[] = $name;
                }
            }
            $j++;
        }
        
        $ret = Dev::export($data,$indent);
        $ret = str_replace($search,$replace,$ret);
        return $ret;
    }
}