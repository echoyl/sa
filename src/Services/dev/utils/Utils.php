<?php
namespace Echoyl\Sa\Services\dev\utils;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Illuminate\Support\Arr;

class Utils
{

    public static $value_type_map = [
        'select'=>'select',
        'selects'=>'select',
        'search_select'=>'debounceSelect',
        'textarea'=>'textarea',
        'image'=>'uploader',
        'file'=>'uploader',
        'datetime'=>'dateTime',
        'date'=>'date',
        'switch'=>'switch',
        'cascader'=>'cascader',
        'cascaders'=>'cascader',
        'pca'=>'pca',
        'tmapInput'=>'tmapInput',
        'bmapInput'=>'bmapInput',
        'tinyEditor'=>'tinyEditor',
        'price'=>'digit',
        'digit'=>'digit',
        'confirm'=>'confirm',
        'radioButton'=>'select',
        'checkbox'=>'select',
        'aliyunVideo'=>'aliyunVideo',
        'modalSelect'=>'modalSelect',
        'saTransfer'=>'saTransfer',
        'html'=>'html',
        'tmapShow'=>'tmapShow',
        'iconSelect'=>'iconSelect'
    ];

    public static $title_arr = [
        'created_at'=>'创建时间',
        'updated_at'=>'最后更新时间',
        'displayorder'=>'排序权重',
    ];


    public static function packageTypes()
    {
        return [
            ['value' =>DevService::appname(), 'label' => '项目'],
            ['value' => 'plugin', 'label' => '插件'], 
            ['value' => 'system', 'label' => '系统']
        ];
    }

    public static function packageTypeArr()
    {
        return collect(self::packageTypes())->pluck('value')->toArray();
    }

    public static function uncamelize($camelCaps,$separator='_')
    {
        if(is_array($camelCaps))
        {
            $data = [];
            foreach($camelCaps as $item)
            {
                $data[] = self::uncamelize($item,$separator);
            }
            return $data;
        }else
        {
            return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
        }
        
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
                $deep = self::toTree([$field]);
                if(!isset($data['models'][$first_field]['models'][$field[0]]))
                {
                    $data['models'][$first_field]['models'][$field[0]] = ['columns'=>[],'models'=>[]];
                }
                $data['models'][$first_field]['models'][$field[0]]['columns'] = array_merge($data['models'][$first_field]['models'][$field[0]]['columns'],$deep['models'][$field[0]]['columns']);
                //$data['models'][$first_field]['models'][$field[0]]['models'] = array_merge($data['models'][$first_field]['models'][$field[0]]['models'],$deep['models']);
            }
        }
        //Log::channel('daily')->info('toTree:',$data);
        return $data;
    }
    /**
     * Undocumented function
     *
     * @param [type] $trees 处理过的选择字段模型名称数据
     * @param integer $indent 格式化代码的缩进数量
     * @param integer $pmodel_id 主模型id
     * @param integer $i 递归的次数
     * @return void
     */
    public static function withTree($trees,$indent = 0,$pmodel_id = 0,$i = 0)
    {
        //d($trees);
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
                //现在数字name 为 relation的id了
                $relation = (new Relation())->where(['id'=>$name])->first();
                //$model = (new Model())->where(['id'=>$name])->first();
                
            }else
            {
                $relation = (new Relation())->where(['model_id'=>$pmodel_id,'name'=>$name])->first();
            }
            if(!$relation)
            {
                return '';
            }
            $name = $relation['name'];

            //

            //$name = $relation?$relation['name']:$name;
            // if($i == 1)
            // {
            //     d($name,$pmodel_id,$relation);
            // }

            if(isset($tree['models']) && !empty($tree['models']))
            {
                
                $replace[] = self::withTree($tree['models'],$indent + 1,$relation['foreign_model_id'],$i+1);
                $sear = 'sear_'.$j;
                $search[] = $sear;
                $inner_with = '->with('.$sear.')';
                // d($inner_with);
            }else
            {
                $inner_with = '';
            }
            //读取关系数据
            
            
            $filter_where = '';
            //筛选条件转移至 model 中
            // if($relation && $relation['filter'])
            // {
            //     $filter = json_decode($relation['filter'],true);
            //     $filter_where = '->where('.json_encode($filter).')';
            // }
            if(isset($tree['columns']) && !empty($tree['columns']))
            {
                $data[$name] = '@phpfunction($q'.$i.'){$q'.$i.'->select('.json_encode($tree['columns']).')'.$filter_where.$inner_with.';}@endphp';
            }else
            {
                if($inner_with)
                {
                    $data[$name] = '@phpfunction($q'.$i.'){$q'.$i.$filter_where.$inner_with.';}@endphp';
                }else
                {
                    if($filter_where)
                    {
                        //有过滤条件
                        $data[$name] = '@phpfunction($q'.$i.'){$q'.$i.$filter_where.';}@endphp';
                    }else
                    {
                        $data[] = $name;
                    }
                }
            }
            $j++;
        }
        $ret = Dev::export($data,$indent);
        $ret = str_replace($search,$replace,$ret);
        return $ret;
        
    }

    /**
     * 通过菜单获取包含了图片属性的字段信息
     *
     * @param [菜单] $menu
     * @return array
     */
    public static function getImageFieldFromMenu($menu)
    {
        $desc = Arr::get($menu,'desc');
        
        if(!$desc)
        {
            return [];
        }

        $desc = json_decode($desc,true);

        $tabs = Arr::get($desc,'tabs',[]);

        $fields = [];

        $value_map = array_flip(self::$value_type_map);

        foreach($tabs as $tab)
        {
            $formColumns = Arr::get($tab,'formColumns',[]);
            foreach($formColumns as $cf)
            {
                $columns = Arr::get($cf,'columns',[]);
                foreach($columns as $col)
                {
                    $type = Arr::get($col,'valueType');
                    $type = $value_map[$type]??$type;
                    if(!in_array($type,['image','file','tinyEditor','mdEditor']))
                    {
                        continue;
                    }
                    $field = Arr::get($col,'dataIndex');
                    if($field)
                    {
                        $fields[] = [$field,$type];
                    }
                }
            }
        }
        return $fields;
    }
}