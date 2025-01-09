<?php
namespace Echoyl\Sa\Services\dev\utils;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\crud\CrudService;
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
        'iconSelect'=>'iconSelect',
        'saSlider'=>'saSlider',
        'config'=>'config'
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

        $desc = is_string($desc)?json_decode($desc,true):$desc;

        $tabs = Arr::get($desc,'tabs',[]);

        $fields = [];

        $value_map = array_flip(self::$value_type_map);

        foreach($tabs as $tab)
        {
            $formColumns = Arr::get($tab,'formColumns',[]);//表单行
            foreach($formColumns as $cf)
            {
                $columns = Arr::get($cf,'columns',[]);//行中的列
                foreach($columns as $col)
                {
                    $type = Arr::get($col,'valueType');//检测每列类型
                    $type = $value_map[$type]??$type;
                    $field = Arr::get($col,'dataIndex');
                    if(in_array($type,['formList','saFormList']))
                    {
                        $fields[] = [$field,'deep',['desc'=>['tabs'=>[
                            ['formColumns'=>$col['columns']]
                        ]]]];
                    }
                    if(!in_array($type,['image','file','tinyEditor','mdEditor']))
                    {
                        continue;
                    }
                    
                    if($field)
                    {
                        $fields[] = [$field,$type,''];
                    }
                }
            }
        }
        return $fields;
    }

    public static function parseImgField($post_data,$field,$originData = false,$encode = true)
    {
        $post_data['originData'] = $originData;//原始数据 更新后需要删除该文件
        [$imgf,$type] = $field;
        $config = [
            'data'=>$post_data,'col'=>['name'=>$imgf,'type'=>$type,'default'=>''],
        ];
        $cs = new CrudService($config);
        //make后的图片数据变成了json需要重新转换一下
        $post_data = $cs->make($type,[
            'encode'=>$encode
        ]);
        if(isset($post_data[$imgf]) && $post_data[$imgf] && ($type == 'image' || $type == 'file'))
        {
            $post_data[$imgf] = is_string($post_data[$imgf])?json_decode($post_data[$imgf],true):$post_data[$imgf];
        }
        Arr::forget($post_data,'originData');
        return $post_data;
    }

    /**
     * 根据dev菜单的form配置将字段内容decode或encode 用于配置页面或 jsonForm
     *
     * @param array $post_data 表单数据
     * @param boolean $dev_menu 菜单数据 格式为['desc'=>['tabs'=>[['formColumns'=>[]]]],'form_config'=>[]] desc为已生成的前端页面可使用的配置数据
     * @param boolean $originData 原始数据
     * @param string $type encode/decode 编码 - 入数据库/解码 - 页面展示 自动补齐url等
     * @param array $deep_img_fields 手动设置需要解析的字段索引
     * @param boolean $is_array 是否是数组 如果是数组则需要循环处理 比如formlist saFormlist
     * @return array
     */
    public static function parseImageInPage($post_data,$dev_menu = false,$originData = false,$type = 'encode',$deep_img_fields = [],$is_array = false)
    {
        $encode = $type == 'encode'?true:false;
        $img_fields = Utils::getImageFieldFromMenu($dev_menu);
        $img_fields = array_merge($img_fields,$deep_img_fields);
        foreach($img_fields as $field)
        {
            [$imgf,$vtype,$deep_menu] = [$field[0],$field[1],$field[2]??[]];
            if($vtype == 'deep')
            {
                //如果是formlist 类型需要深层次去检索
                //d($deep_menu);
                $deep_data = self::parseImageInPage(Arr::get($post_data,$imgf),$deep_menu,Arr::get($originData,$imgf),$type,[],true);
                Arr::set($post_data,$imgf, $deep_data);
            }else
            {
                if($is_array)
                {
                    if($post_data)
                    {
                        foreach($post_data as $key=>$pd)
                        {
                            $post_data[$key] = self::parseImgFields($imgf,$pd,$field,Arr::get($originData,$key),$encode,$vtype);
                        }
                    }
                    
                }else
                {
                    $post_data = self::parseImgFields($imgf,$post_data,$field,$originData,$encode,$vtype);
                }
            }
        }
        return $post_data;
    }

    public static function parseImgFields($imgf,$post_data,$field,$originData,$encode,$type)
    {
        if(is_string($imgf))
        {
            $post_data = self::parseImgField($post_data,$field,$originData,$encode);
        }elseif(is_array($imgf))
        {
            $d = Arr::get($post_data,implode('.',$imgf));
            if($d)
            {
                $name = array_pop($imgf);
                $top = Arr::get($post_data,implode('.',$imgf));
                $top_ata = Arr::get($originData,implode('.',$imgf));
                $top = self::parseImgField($top,[$name,$type],$top_ata,$encode);
                Arr::set($post_data,implode('.',$imgf), $top);
            }
        }
        return $post_data;
    }
}