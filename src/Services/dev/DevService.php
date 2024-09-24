<?php
namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\utils\Dev;
use Echoyl\Sa\Services\dev\utils\ExportColumn;
use Echoyl\Sa\Services\dev\utils\FormItem;
use Echoyl\Sa\Services\dev\utils\Schema as UtilsSchema;
use Echoyl\Sa\Services\dev\utils\TableColumn;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DevService
{

    var $msg = [];

    public $tpl_path = __DIR__.'/tpl/';

    public static function appname()
    {
        return env('APP_NAME','');
    }

    public function createModelSchema($data)
    {
        return (new UtilsSchema)->createModelSchema($data);
    }

    public function tabel2SchemaJson($name,$parent_id)
    {
        return (new UtilsSchema)->tabel2SchemaJson($name,$parent_id);
    }

    public static function getPath($val, $menus,$field = 'name')
    {
        $path = Utils::getPath($val, $menus,$field);
        //Log::channel('daily')->info('getPath:',['path'=>$path,'val'=>$val]);
        $pl = count($path);
        //加一个判断如果最后一个不是项目名称就自动追加name 限于获取模型的路径
        $type = Arr::get($val,'admin_type','');
        
        if(isset($val['admin_type']) && $type != 'system' && $pl > 0)
        {
            $app_name = self::appname();
            if($path[$pl-1] != $app_name)
            {
                $path[] = $app_name;
            }
        }
        return $path;
    }

    public function line($msg)
    {
        $this->msg[] = $msg;
    }

    /**
     * 获取所有模型 
     *
     * @param boolean $flush 如果为true表示重新获取最新数据
     * @return void
     */
    public function allModel($flush = false)
    {
        static $data = [];
        if(empty($data) || $flush)
        {
            $data = Cache::get('devAllModel');
            if(!$data || $flush)
            {
                $data = (new Model())->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
                Cache::set('devAllModel',$data);
            }
        }
        
        return $data;
    }

    public function allMenu($flush = false)
    {
        
        static $data = [];
        if((empty($data)  || $flush) && Schema::hasTable('dev_menu'))
        {
            $data = Cache::get('devAllMenu');
            if(!$data || $flush)
            {
                $data = (new Menu())->with(['adminModel'])->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
                Cache::set('devAllMenu',$data);
            }
        }
        
        return $data;
    }

    /**
     * 生成模型关系的use namespace 和 代码定义部分
     *
     * @param [type] $model
     * @param [array] $has_model 使用过的模型数据
     * @return void
     */
    public function createModelRelation($model,$useModelArr = [])
    {
        $model_id = $model['id'];
        $relations = (new Relation())->where(['model_id'=>$model_id])->with(['foreignModel'])->get()->toArray();
        $namespace_data = [];
        $hasone_data = [];

        $setting = $model['setting']?json_decode($model['setting'],true):[];

        $use_items = [];
        $use = '';
        $traits = [
            'soft_delete'=>['SoftDeletes','use Illuminate\Database\Eloquent\SoftDeletes;'],
            'with_system_admin_id'=>['InsertAdminId','use Echoyl\Sa\Helpers\InsertAdminId;'],
            'global_data_search'=>['AdminDataSearch','use Echoyl\Sa\Helpers\AdminDataSearch;'],
            'global_post_check'=>['AdminPostCheck','use Echoyl\Sa\Helpers\AdminPostCheck;'],
        ];
        foreach($traits as $tkey=>$tval)
        {
            if(Arr::get($setting,$tkey))
            {
                $namespace_data[] = $tval[1];
                $use_items[] = $tval[0];
            }
        }
        
        if(!empty($use_items))
        {
            $use = "use ".implode(',',$use_items).';';
        }

        if(empty($relations))
        {
            return [$namespace_data,$hasone_data,$use];
        }

        $hasone_tpl ="
    public function _name()
    {
        return \$this->has_type(_modelName::class,'_foreignKey','_localKey')_filterWhere_orderBy_withDefault;
    }";
        
        $has_model = [ucfirst($model['name'])];
        //$useModelArr = [];//使用过的模型数据
        foreach($relations as $val)
        {
            if(!in_array($val['type'],['one','many']))
            {
                continue;
            }
            $foreign_model = $val['foreign_model'];

            //$foreign_model_name = ucfirst(array_pop($foreign_model_names));

            $foreign_model_name = ucfirst($foreign_model['name']);

            if($model['parent_id'] != $foreign_model['parent_id'])
            {
                //如果单个模型一张表多个字段对应同一个模型 则跳过、、 比如一张表中含有 省市区三个字段对应 pca表中的同一个字段
                if(isset($useModelArr[$foreign_model['id']]))
                {
                    $foreign_model_name = $useModelArr[$foreign_model['id']];
                }else
                {
                    //同一个文件夹下面的模型不需要添加namespace
                                    
                    [$namespace,$f_model_name] = $this->getNamespace($foreign_model,$has_model);
                    $useModelArr[$foreign_model['id']] = $f_model_name;
                    $has_model[] = $f_model_name;
                    $foreign_model_name = $f_model_name;
                    $namespace_data[] = $namespace.';';
                }
            }

            //读取with_default
            $with_default = '';
            if($val['with_default'])
            {
                $with_default = json_decode($val['with_default'],true);
                if(!empty($with_default))
                {
                    $with_default = '->withDefault('.Dev::export($with_default,2).')';
                }
            }
            //加入筛选条件 应该只有1对多的时候才加入
            $filter_where = '';
            if($val['filter'] && $val['type'] == 'many')
            {
                $filter = json_decode($val['filter'],true);
                //关联模型不支持变量参数过滤掉 
                $_filter = [];
                foreach($filter as $f)
                {
                    if(isset($f[1]) && ($f[1] == 'in' || $f[1] == 'between') && is_array($f[2]))
                    {
                        $filter_where .= '->'.($f[1] == 'in'?'whereIn':'whereBetween').'("'.$f[0].'",'.json_encode($f[2]).')';
                        continue;
                    }
                    if(isset($f[2]) && is_string($f[2]) && strpos($f[2],'this.') !== false)
                    {
                        continue;
                    }
                    $_filter[] = $f;
                }
                if(!empty($_filter))
                {
                    $filter_where .= '->where('.json_encode($_filter).')';
                }
                
            }
            //加入排序条件
            $order_by = '';
            if($val['order_by'])
            {
                $_order_by = json_decode($val['order_by'],true);
                foreach($_order_by as $ob)
                {
                    $order_by .= '->orderBy("'.$ob[0].'","'.$ob[1].'")';
                }
                
            }

            $hasone_data[] = str_replace([
                '_name',
                '_modelName',
                '_foreignKey',
                '_localKey',
                '_type',
                '_withDefault',
                '_filterWhere',
                '_orderBy'
            ],[
                $val['name'],
                $foreign_model_name,
                $val['foreign_key'],
                $val['local_key'],
                ucfirst($val['type']),
                $with_default,
                $filter_where,
                $order_by
            ],$hasone_tpl);
        }
        return [$namespace_data,$hasone_data,$use];
    }

    /**
     * 生成模型文件
     *
     * @param [object] $data 模型的数据信息
     * @return void
     */
    public function createModelFile($data)
    {
        $all = $this->allModel(true);

        $names = array_reverse(self::getPath($data, $all));
        
        //d($names);

        $table_name = implode('_',$names);
        $name = ucfirst(array_pop($names));
        
        $namespace = '';
        if(!empty($names))
        {
            $namespace = '\\'.implode('\\',$names);
            $model_file_path = implode('/',[app_path('Models'),implode('/',$names),$name.'.php']);
        }else
        {
            $model_file_path = implode('/',[app_path('Models'),$name.'.php']);
        }
        $tpl_name = 'model';
        if($data['leixing'] == 'category')
        {
            $tpl_name = 'model2';
        }elseif($data['leixing'] == 'auth')
        {
            $tpl_name = 'modelAuth';
        }
        $content = file_get_contents(implode('/',[$this->tpl_path,$tpl_name.'.txt']));

        $this->line('开始生成model文件：');

        [$use_namespace,$crud_config,$parse_columns,$useModelArr] = $this->createControllerRelation($data);
        [$use_namespace2,$tpl,$use] = $this->createModelRelation($data,$useModelArr);
        
        $use_namespace = array_unique(array_merge($use_namespace,$use_namespace2));
    
        //d($use_namespace,$tpl);

    $columns_function = 'public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = $parse_columns$;
        }
        return $data;
    }';

        if(!empty($parse_columns))
        {
            //$parse_columns = preg_replace(['/\$parse_columns\$/'],HelperService::format_var_export($parse_columns,4),$columns_function);
            $parse_columns = preg_replace(['/\$parse_columns\$/'],Dev::export($parse_columns,3),$columns_function);
        }else
        {
            $parse_columns = '';
        }

        //检测文件是否已经存在 存在的话将自定义代码带入
        [$customer_code,$customer_construct,$customer_namespace] = $this->customerCode($model_file_path);

        $replace_arr = [
            '/\$use_namespace\$/'=> implode("\r",$use_namespace),
            '/\$use\$/'=> $use,
            '/\$namespace\$/'=>$namespace,
            '/\$table_name\$/'=>$table_name,
            '/\$name\$/'=>$name,
            '/\$model_id\$/'=>$data['id'],
            '/\$relationship\$/'=>implode("\r",$tpl),
            '/\$parse_columns\$/'=>$parse_columns,
            '/\$customer_code\$/'=>$customer_code,
            '/\$customer_construct\$/'=>$customer_construct,
            '/\$customer_namespace\$/'=>$customer_namespace,
            // '/\$hasone\$/'=>implode("\r",$hasone_data),
            // '/\$hasmany\$/'=>$hasmany_data
        ];

        $search = $replace = [];
        
        foreach($replace_arr as $key=>$val)
        {
            $search[] = $key;
            $replace[] = $val;
        }

        $content = preg_replace($search,$replace,$content);
        $this->createFile($model_file_path,$content,true);
        return;
    }

    /**
     * 获取文件的自定义代码信息
     *
     * @param string $file_path 文件路径
     * @param array $customer_init 初始化自定义代码设置 只在创建文件时检测
     * @return void
     */
    public function customerCode($file_path,$customer_init = [])
    {
        $replace = [
            ['/customer code start(.*)\/\/customer code end/s'],//自定义代码
            ['/customer construct start(.*)\/\/customer construct end/s'],//自定义构造函数代码
            ['/customer namespace start(.*)\/\/customer namespace end/s'],//自定义namespace
            ['/customer property start(.*)\/\/customer property end/s'],//自定义属性
        ];
        $ret = [];
        if(file_exists($file_path))
        {
            $old_content = file_get_contents($file_path);
            foreach($replace as $val)
            {
                $match = [];
                preg_match($val[0],$old_content,$match);
                $code = '';
                if(!empty($match))
                {
                    //已存在的文件该段不做覆盖
                    $code = trim($match[1]);
                }
                $ret[] = $code;
            }
        }else
        {
            if(!empty($customer_init))
            {
                $ret = $customer_init;
            }else
            {
                $ret = ['','','',''];
            }
        }
        return $ret;
    }

    public function createControllerFile($data,$customer_init = [])
    {
        $all = $this->allModel(true);

        $names = array_reverse(self::getPath($data, $all));

        $name = ucfirst(array_pop($names));

        $namespace = '';
        if(!empty($names))
        {
            $namespace = '\\'.implode('\\',$names);
            $model_file_path = implode('/',[app_path('Http/Controllers/admin'),implode('/',$names),$name.'Controller.php']);
        }else
        {
            $model_file_path = implode('/',[app_path('Http/Controllers/admin'),$name.'Controller.php']);
        }
        $tpl_name = 'controller';
        if($data['leixing'] == 'category')
        {
            $tpl_name = 'controller2';
        }
        $content = file_get_contents(implode('/',[$this->tpl_path,$tpl_name.'.txt']));

        //读取控制器主模型的关联信息
        [$use_namespace,$crud_config,$parse_columns] = $this->createControllerRelation($data);

        $this->line('开始生成controller文件：');
        //HelperService::format_var_export($parse_columns,3);

        //生成基础配置 with_column，search_config，with_sum,with_count
        $use_namespace = $parse_columns = [];
        
        //检测文件是否已经存在 存在的话将自定义代码带入
        [$customer_code,$customer_construct,$customer_namespace,$customer_property] = $this->customerCode($model_file_path,$customer_init);

        $replace_arr = [
            '/\$namespace\$/'=>$namespace,
            '/\$modelname\$/'=>$name,
            '/\$name\$/'=>$name,
            '/\$crud_config\$/'=>implode("\r\t\t",$crud_config),
            '/\$use_namesapce\$/'=>implode("\r",$use_namespace),
            //'/\$parse_columns\$/'=>HelperService::format_var_export($parse_columns,3),
            '/\$customer_code\$/'=>$customer_code,
            '/\$customer_construct\$/'=>$customer_construct,
            '/\$customer_property\$/'=>$customer_property,
            '/\$customer_namespace\$/'=>$customer_namespace,
            '/\$app_name\$/'=>env('APP_NAME',''),
            // '/\$hasone\$/'=>implode("\r",$hasone_data),
            // '/\$hasmany\$/'=>$hasmany_data
        ];

        $search = $replace = [];
        
        foreach($replace_arr as $key=>$val)
        {
            $search[] = $key;
            $replace[] = $val;
        }

        $content = preg_replace($search,$replace,$content);
        $this->createFile($model_file_path,$content,true);
        return;
    }

    public function createControllerRelation($model)
    {
        $model_id = $model['id'];
        $namespace_data = [];
        $parse_columns = [];
        $crud_config = [];
        $with_sum = $with_count = $with_columns = $search_config = $can_be_null_columns = [];//配置处理好后 直接返回代码了

        $relations = (new Relation())->where(['model_id'=>$model_id])->with(['foreignModel.relations'])->get()->toArray();

        //循环检测列中支持搜索的字段
        $columns = $model['search_columns']?json_decode($model['search_columns'],true):[];
        foreach($columns as $val)
        {
            $search_config[] = ['name'=>$val['name'],'columns'=>$val['columns'],'where_type'=>$val['type']];;
        }

        

        $has_model = [ucfirst($model['name'])];
        $all_models = [$model['name']=>$model['name']];
        $all_relations = [];
        
        $with_trees = [];
        $useModelArr = [];//使用过的模型数据
        if(!empty($relations))
        {
            foreach($relations as $key=>$val)
            {
                
    
                if($val['can_search'])
                {
                    //这里的关联名称需要转化小驼峰->下划线模式 //这里加入withdefault 的空逻辑
                    $default_with_value = '';
                    if($val['with_default'])
                    {
                        $with_default = json_decode($val['with_default'],true);
                        foreach($with_default as $wd)
                        {
                            if($wd)
                            {
                                //获取第一个有值的数据
                                $default_with_value = $wd;
                                break;
                            }
                        }
                    }
                    $search_config[] = [
                        'name'=>$val['name'],
                        'columns'=>explode(',',$val['search_columns']),
                        'type'=>'has',
                        'default'=>$default_with_value
                    ];
                }

                if($val['type'] == 'many')
                {
                    //hasMany 检测是否需要with_count 和 with_sum 的字段
                    if($val['with_count'])
                    {
                        $with_count[] = $val['name'];
                        //计算数据数量时检测是否有链接
                        // $menu = (new Menu())->where(['admin_model_id'=>$val['foreign_model_id']])->first();
                        // if($menu)
                        // {
                        //     $path = self::getPath($menu,(new Menu)->get(),'path');
                        //     $parse_columns[] = [
                        //         'name'=>$val['name'].'_count',
                        //         'type'=>'link',
                        //         'path'=>'/'.implode('/',array_reverse($path)),
                        //         'uri'=>[[$val['foreign_key'],$val['local_key']]]
                        //     ];
                        // }
                    }
                    if($val['with_sum'])
                    {
                        $_with_sum = explode(',',$val['with_sum']);
                        foreach($_with_sum as $with_sum_name)
                        {
                            $with_sum[] = [$val['name'],$with_sum_name];
                        }
                    }
                }

                $foreign_model = $val['foreign_model'];

                if(!$foreign_model)
                {
                    d($val);
                }
                
                if(isset($useModelArr[$foreign_model['id']]))
                {
                    $f_model_name = $useModelArr[$foreign_model['id']];
                }else
                {
                    [$namespace,$f_model_name] = $this->getNamespace($foreign_model,$has_model);
                    $useModelArr[$foreign_model['id']] = $f_model_name;
                    $namespace_data[] = $namespace.';';
                }
    
                
                
    
                $has_model[] = $f_model_name;
                $all_models[$val['local_key']] = $f_model_name;
                if(!isset($all_relations[$val['local_key']]))
                {
                    $all_relations[$val['local_key']] = $val;
                }
                

                if($val['is_with'])
                {
                    if(in_array($val['type'],['one','many']))
                    {
                        //关联模型的字段
                        //检测是否填写了需要关联模型的字段
                        if($val['select_columns'])
                        {
                            //解析字段
                            $fields = collect(explode(',',$val['select_columns']))->map(function($v){
                                return explode('-',$v);
                            })->sort(function($a,$b){
                                return count($a) < count($b);
                            })->toArray();
                            $with_tree = Utils::toTree($fields);
                        }else
                        {
                            $with_tree = [];
                        }
                        //将需要处理的with数据先存入数组中
                        $with_trees[$val['name']] =  $with_tree;
                    }
                    
                    if($val['type'] == 'one')
                    {
                        //1对1时候 还需要关联对接 转换关联数据
                        //name需要驼峰转下划线
                        $parse_columns[] = [
                            'name'=>Utils::uncamelize($val['name']),
                            'type'=>'model',
                            'class'=>'@php'.$f_model_name.'::class@endphp',
                            'foreign_key'=>$val['foreign_key']
                        ];
    
                    }
                }

                if($val['is_with_in_page'])
                {
                    //1对1时候 还需要关联对接 转换关联数据
                    $parse_columns[] = [
                        'name'=>$val['name'],
                        'type'=>'select_columns',
                        'class'=>'@php'.$f_model_name.'::class@endphp',
                        'with'=>true,
                        'columns'=>$val['in_page_select_columns']?explode(',',$val['in_page_select_columns']):[]
                    ];
                }
            }
            $with_columns = Utils::withTree($with_trees,2,$model_id);
            //d($with_columns);
        }

        if(!empty($with_columns))
        {
            $crud_config[] = '$this->with_column = '.$with_columns.';';
            //$crud_config[] = str_replace($with_columns_search,$with_columns_replace,'$this->with_column = '.(Dev::export($with_columns,2)).';');
        }
        if(!empty($search_config))
        {
            $crud_config[] = '$this->search_config = '.(Dev::export($search_config,2)).';';
        }
        if(!empty($with_sum))
        {
            //d(json_encode($with_sum));
            $crud_config[] = '$this->with_sum = '.(Dev::export($with_sum,2)).';';
        }
        if(!empty($with_count))
        {
            $crud_config[] = '$this->with_count = '.(json_encode($with_count)).';';
        }

        //添加唯一字段检测
        if($model['unique_fields'])
        {
            $_unique_fields = json_decode($model['unique_fields'],true);
            // $unique_fields = [];
            // foreach($_unique_fields as $uf)
            // {
            //     $unique_fields[] = $uf['columns'];
            // }
            // $crud_config[] = '$this->uniqueFields = '.(json_encode($unique_fields)).';';
            $crud_config[] = '$this->uniqueFields = '.(Dev::export($_unique_fields,2)).';';
        }


        

        //生成parse_columns 数组
        if($model['columns'])
        {
            $columns = json_decode($model['columns'],true);
            foreach($columns as $column)
            {
                //字段是否可为空
                $empty = Arr::get($column,'empty',0);
                if($empty)
                {
                    $can_be_null_columns[] = $column['name'];
                }
                if(!isset($column['form_type']) || in_array($column['form_type'],['textarea','dateTime','datetime','digit']))
                {
                    //不存在type 或者一些不需要parse的字段 直接过滤
                    continue;
                }
                //$form_data = $column['form_data']??'';
                $setting = $column['setting']??[];
                $form_type = $column['form_type'];
                $default_value = $column['default']??'';

                $int_value_form_types = ['price'];//需要直接转化为int类型
                $int_value_form_types_need_relation = ['select','search_select','radioButton'];

                if(in_array($form_type,$int_value_form_types) || (in_array($form_type,$int_value_form_types_need_relation) && isset($all_models[$column['name']])))
                {
                    $default_value = $default_value?intval($default_value):0;
                }

                //['name' => 'shop_id', 'type' => 'search_select', 'default' => '0','data_name'=>'shop','label'=>'name'],
                $d = [
                    'name' => $column['name'], 
                    'type' => $form_type, 
                    'default' => $default_value,
                ];

                $table_menu = isset($column['table_menu']) && $column['table_menu'];
                $label = $setting['label']??'';
                $value = $setting['value']??'';
                $children = $setting['children']??'';

                $relation = $all_relations[$column['name']]??false;//当前字段有的关联

                // if($column['name'] == 'leimu_id')
                // {
                //     d($relation);
                // }
                $_columns = [$label?:'title',$value?:'id'];
                if($relation)
                {
                    if($relation['filter'])
                    {
                        $d['where'] = json_decode($relation['filter'],true);
                    }

                    if($relation['in_page_select_columns'])
                    {
                        $_columns = explode(',',$relation['in_page_select_columns']);
                    }
                }

                if(in_array($form_type,['select','selects','radioButton']))
                {
                    if(isset($all_models[$column['name']]))
                    {
                        $d['class'] = "@php".$all_models[$column['name']]."::class@endphp";
                        //如果是select 且设置了tabel menu，那么form_data设置的label 和value 
                        //新增数据筛选配置
                        //$filter = $clo
                        if($table_menu && $label && $value)
                        {
                            //$d['columns'] = ["{$label} as label","{$value} as value",$label,$value];
                            $_columns = array_merge($_columns,["{$label} as label","{$value} as value"]);
                        }
                        $d['no_category'] = true;
                        $d['columns'] = $_columns;
                    }else
                    {
                        if(isset($setting['json']) && $setting['json'])
                        {
                            //d(json_decode($column['form_data'],true));
                            if(is_string($setting['json']))
                            {
                                $d['data'] = json_decode($setting['json'],true);
                            }else
                            {
                                $d['data'] = $setting['json'];
                            }
                            
                            //d($d['data']);
                        }else
                        {
                            $d['data'] = '[]';
                        }
                    }
                    $d['with'] = true;
                    //select的类型会可以检测是否需要table_menu 设置
                    if($table_menu)
                    {
                        $d['table_menu'] = true;
                    }
                }
                if($form_type == 'switch')
                {
                    //switch 也支持 table_menu
                    $valueEnum = [];
                    if(isset($setting['close']) && isset($setting['open']))
                    {
                        $valueEnum = [
                            ['label'=>$setting['close'],'value'=>0],
                            ['label'=>$setting['open'],'value'=>1]
                        ];
                    }
                   
                    // $_value = [];
                    // foreach($valueEnum as $key=>$val)
                    // {
                    //     $_value[strval($key)] = $val;
                    // }
                    //d($valueEnum);
                    
                    
                    $d['default'] = $default_value?1:0;
                    if($table_menu)
                    {
                        
                        $d['table_menu'] = true;
                    }else
                    {
                        //continue;
                    }
                    if(!empty($valueEnum))
                    {
                        $d['with'] = true;
                        $d['data'] = $valueEnum;
                    }

                }
                if($form_type == 'search_select' && isset($all_relations[$column['name']]))
                {
                    $d['data_name'] = Utils::uncamelize($all_relations[$column['name']]['name']);
                    if($label)
                    {
                        $d['label'] = $label;
                    }
                    if($value)
                    {
                        $d['value'] = $value;
                    }
                }
                if($form_type == 'cascaders' || $form_type == 'cascader')
                {
                    if(isset($all_models[$column['name']]))
                    {
                        $d['class'] = "@php".$all_models[$column['name']]."::class@endphp";
                        $d['with'] = true;
                    }
                    
                    if($label && $value && $children)
                    {
                        $d['fields'] = ['id'=>$value,'title'=>$label,'children'=>$children];
                    }
                }
                //省市区选择器
                if($form_type == 'pca')
                {
                    $d['default'] = "__unset";
                    $d['level'] = $setting['pca_level']??1;
                    $d['topCode'] = Arr::get($setting,'pca_topCode','');
                }
                //modalSelect
                if($form_type == 'modalSelect')
                {
                    if($relation)
                    {
                        $d['value'] = $relation['foreign_key'];
                    }
                }
                //富文本
                $parse_columns[] = $d;
            }
        }

        if(!empty($can_be_null_columns))
        {
            $crud_config[] = '$this->can_be_null_columns = '.(json_encode($can_be_null_columns)).';';
        }
    
        return [$namespace_data,$crud_config,$parse_columns,$useModelArr];
    }

    /**
     * 获取模型的的namespace
     *
     * @param [type] $foreign_model
     * @param array $exist_names
     * @return void
     */
    public function getNamespace($foreign_model,$exist_names = [])
    {
        $foreign_model_names = array_reverse(self::getPath($foreign_model,$this->allModel()));

        $foreign_model_name = ucfirst(array_pop($foreign_model_names));

        $namespace_prefix = 'App\Models';
        
        if(!empty($foreign_model_names))
        {
            $namespace_prefix .= '\\'.implode('\\',$foreign_model_names);
        }
        $namespace_prefix .= '\\'.$foreign_model_name;

        if($foreign_model['admin_type'] == 'system')
        {
            //系统模型需要获取单独的namespace 前缀
            //先检测下如果项目中重写了系统的模型 那就直接引入项目中复写的模型
            if(!class_exists($namespace_prefix))
            {
                $namespace_prefix = str_replace('App\Models','Echoyl\Sa\Models',$namespace_prefix);
            }
        }

        //如果模型名字一样的话 需要添加一个别名 
        
        if(in_array($foreign_model_name,$exist_names))
        {
            if(!empty($foreign_model_names))
            {
                $foreign_model_as_name = ucfirst($foreign_model_names[count($foreign_model_names) - 1]).$foreign_model_name;
            }else
            {
                $foreign_model_as_name = 'Models'.$foreign_model_name;
            }
            $foreign_model_name = $foreign_model_as_name;
            $namespace_prefix .= ' as '.$foreign_model_as_name;
        }
        return ['use '.$namespace_prefix,$foreign_model_name,$namespace_prefix];
    }

    public function createFile($file,$content,$force = false)
    {
        //检测文件夹是否存在
        $pathinfo = pathinfo($file);
        $path = $pathinfo['dirname'];
        if(!is_dir($path))
        {
            mkdir($path,0755,true);
            $this->line($path.' 文件夹创建成功');
        }else
        {
            $this->line($path.' 文件夹已存在');
        }


        if(file_exists($file) && !$force)
        {
            $this->line($file.' 文件已存在');
        }else
        {
            $fopen = fopen($file,'w');
            fwrite($fopen,$content);
            fclose($fopen);
            $this->line($file.' 创建成功');
        }
        return;
    }

    public function getModelsTree($admin_type = '',$all_selectable = false)
    {
        if($admin_type)
        {
            return $this->getModelsTreeData($admin_type,$all_selectable);
        }else
        {
            //获取全部的话 将系统模型归类到一起
            $system = $this->getModelsTreeData(['system'],$all_selectable);
            $app = $this->getModelsTreeData([self::appname()],$all_selectable);
            return array_merge($app,[[
                'id'=>0,
                'title'=>'系统相关',
                'value'=>0,
                'isLeaf'=>false,
                'selectable'=>false,
                'children'=>$system
            ]]);
        }
    }
    /**
     * 获取模型返回树形格式
     *
     * @param string $admin_type
     * @param boolean $all_selectable
     * @return void
     */
    public function getModelsTreeData($admin_type = '',$all_selectable = false)
    {
        $model = new Model();
        $types = $admin_type?:['system',self::appname(),''];
        $data = HelperService::getChildFromData($model->whereIn('admin_type',$types)->get()->toArray(),function($item) use($all_selectable){
            
            return [
                'id'=>$item['id'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>$item['type']?true:false,
                'selectable'=>$item['type'] || $all_selectable ? true:false,
            ];
        },[['type','asc'],['id','asc']]);
        return $data;
    }

    public function getMenusTree($admin_type = '',$all_selectable = false)
    {
        $model = new Menu();
        $types = $admin_type?:['system',env('APP_NAME'),''];

        $data = HelperService::getChildFromData($model->whereIn('type',$types)->get()->toArray(),function($item) use($all_selectable){
            
            return [
                'id'=>$item['id'],
                'label'=>$item['title'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>true,
                'selectable'=>true,
            ];
        },[['state','desc'],['displayorder','desc'],['id','desc']]);

        return $data;
    }

    public function getModelsFolderTree($admin_type = '')
    {
        $types = $admin_type?:['system',env('APP_NAME'),''];
        $model = (new Model())->whereIn('admin_type',$types)->where(['type'=>0]);
        $data = HelperService::getChildFromData($model->get()->toArray(),function($item){
            return [
                'id'=>$item['id'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>$item['type']?true:false,
                //'selectable'=>$item['type']?true:false,
            ];
        },[['displayorder', 'desc'],['type', 'asc'],['id', 'desc']]);
        return $data;
    }

    /**
     * 生成model的某个字段的json 前端配置
     *
     * @param [所选模型] $model
     * @param [每个group配置] $key
     * @return array
     */
    public function modelColumn2JsonForm($model,$keys)
    {
        $data = [];
        
        $menus = $this->allMenu();
        $models = $this->allModel();
        foreach($keys as $row)
        {
            $formItem = new FormItem($row,$model,$menus,$models);
            if($formItem->data)
            {
                $d = $formItem->data;
            }else
            {
                continue;
            }
            $data[] = $d;
        }
        $key_len = count($data);
        if($key_len < 1)
        {
            return $data;
        }
        foreach($data as $key=>$val)
        {
            if(is_string($val))
            {
                continue;
            }
            $span = floor(24 / $key_len);
            if(!isset($val['colProps']))
            {
                $data[$key]['colProps'] = ['span'=>$span];
            }
            
        }
        return $data;
    }

    /**
     * table 列表字段的解析
     *
     * @param [所选的模型] $model
     * @param [列配置] $col
     * @return void
     */
    public function modelColumn2JsonTable($model,$col,$setting = [])
    {
        $menus = $this->allMenu();
        $models = $this->allModel();
        $tableColumn = new TableColumn($col,$model,$menus,$models);
        $data = $tableColumn->data;
        if(!isset($data['width']) && isset($setting['table']) && isset($setting['table']['scroll'])  && isset($setting['table']['scroll']['x']))
        {
            $data['width'] = 100;
        }
        return $data;
    }

    public function modelColumn2Export($model)
    {
        $setting = $model['setting'];
        if(!$setting)return;

        $setting = json_decode($model['setting'],true);
        $exports = Arr::get($setting,'export');
        if(!$exports)return;

        $menus = $this->allMenu();
        $models = $this->allModel();
        foreach($exports as $key=>$export)
        {
            //多模板需要执行
            $columns = HelperService::getFromObject($export,['config','head','columns']);
            if(!$columns)
            {
                continue;
            }
            foreach($columns as $k=>$col)
            {
                $exportColumn = new ExportColumn($col,$model,$menus,$models);
                $col['title'] = $exportColumn->data['title'];
                $columns[$k] = $col;
            }
            //d($columns);
            Arr::set($export,'config.head.columns', array_values($columns));
            $exports[$key] = $export;
        }
        
        $setting['export'] = $exports;
        $_model = new Model();
        $_model->where(['id'=>$model['id']])->update(['setting'=>json_encode($setting)]);

        return;
        
    }

    public static function aliasRoute()
    {
        // Route::group(['namespace' => env('APP_NAME','')], function (){
        //     self::createRoute(self::getMenuByParentId());
        // });
        self::createRoute(self::getMenuByParentId());
        return;
    }

    public static function aliasRouteSystem()
    {
        self::createRoute(self::getMenuByParentId(0,'system'));
        return;
    }

    public static function createRoute($menus,$prefix = [])
    {
        $self = new self();
        if(!$menus)return;
        $app_name = self::appname();
        $only_action_types = ['form','panel','panel2','justTable'];
        //d($menus);
        foreach($menus as $menu)
        {
            //Log::channel('daily')->info('menus name:',['name'=>$menu['path'],'title'=>$menu['title']]);
            $children = self::getMenuByParentId($menu['id']);
            if(!empty($children))
            {
                self::createRoute($children,array_merge($prefix,[$menu['path']]));
            }else
            {
                if($menu['admin_model'])
                {
                    $model = $menu['admin_model'];
                    $name = $model['name'];
                    if($model['type'] == 1)
                    {
                        //检测是否有namespace
                        $model_path = array_reverse(self::getPath($model,$self->allModel()));
                        $_prefix = array_merge($prefix,[$menu['path']]);
                        $controller_prefix = '';
                        //检测如果菜单是项目菜单 指向的是系统模型需要添加控制器文件绝对路径前缀
                        //20230825 - 先检测一遍项目下是否有该控制器 没有的话再指向system下的控制器
                        // if($menu['type'] == $app_name && $model['admin_type'] == 'system')
                        // {
                            
                        //     $_model_path = $model_path;
                        //     $name = array_pop($_model_path);
                        //     $c = !empty($_model_path)?implode("\\",$_model_path).'\\':'';
                        //     if(class_exists('App\Http\Controllers\admin\\'.$c.ucfirst($name).'Controller'))
                        //     {
                        //         //这样设置的话 系统路由中存在项目路由
                        //         $controller_prefix = '\App\Http\Controllers\admin\\'.$c;
                        //     }else
                        //     {
                        //         //项目路由中存在指向系统控制器的路由
                        //         $controller_prefix = '\Echoyl\Sa\Http\Controllers\admin\\'.$c;
                        //     }
                        //     //Log::channel('daily')->info('createRoute form:',['controller_prefix'=>$controller_prefix]);
                        // }
                        //20240419这里只检测指向模型属于系统还是项目，不再设置namespace
                        $_model_path = $model_path;
                        $name = array_pop($_model_path);
                        $c = !empty($_model_path)?implode("\\",$_model_path).'\\':'';
                        if(class_exists('App\Http\Controllers\admin\\'.$c.ucfirst($name).'Controller'))
                        {
                            //这样设置的话 系统路由中存在项目路由
                            $controller_prefix = '\App\Http\Controllers\admin\\'.$c;
                        }else
                        {
                            //项目路由中存在指向系统控制器的路由
                            $controller_prefix = '\Echoyl\Sa\Http\Controllers\admin\\'.$c;
                        }

                        if(count($model_path) > 1)
                        {
                            //默认所有路由都走这里 因为都有一个项目namespace
                            $name = array_pop($model_path);

                            //读取菜单的其它权限设置 单独的action方法独立写路由
                            if($menu['perms'])
                            {
                                $perms = json_decode($menu['perms'],true);
                                $perms = HelperService::json_validate($menu['perms']);
                                if($perms && is_array($perms))
                                {
                                    foreach($perms as $key=>$title)
                                    {
                                        Route::any(implode('/',array_merge($_prefix,[$key])), $controller_prefix.ucfirst($name).'Controller@'.$key);
                                    }
                                }
                            }

                            if(in_array($menu['page_type'],$only_action_types))
                            {
                                //如果是form直接指向控制器方法
                                $key = $menu['path'];
                                Route::any(implode('/',$_prefix), $controller_prefix.ucfirst($name).'Controller@'.$key);
                            }else
                            {
                                Route::resource(implode('/',$_prefix), $controller_prefix.ucfirst($name).'Controller');
                                //Log::channel('daily')->info('createRoute group:',['name'=>implode('/',$_prefix),'to'=>implode('/',$model_path).'/'.ucfirst($name).'Controller',]);
                            }
                            
                            
                        }else
                        {
                            if(in_array($menu['page_type'],$only_action_types))
                            {
                                //如果是form直接指向控制器方法
                                $key = $menu['path'];
                                //Log::channel('daily')->info('createRoute form:',['name'=>implode('/',$_prefix),'to'=>$controller_prefix.ucfirst($name).'Controller@'.$key,]);
                                Route::any(implode('/',$_prefix), $controller_prefix.ucfirst($name).'Controller@'.$key);
                            }else
                            {
                                //Log::channel('daily')->info('createRoute:',['name'=>implode('/',$_prefix),'to'=>$name]);
                                Route::resource(implode('/',$_prefix), $controller_prefix.ucfirst($name).'Controller');
                            }
                            
                        }
                    }
                }
            }
            
        }
        return;
    }

    public static function getMenuByParentId($pid = 0,$type = '')
    {
        $s = new self();
        $type = $type?:env('APP_NAME');
        return collect($s->allMenu())->filter(function ($item) use ($pid,$type) {
            if($pid)
            {
                //有id的话 读取父级id 不在现在菜单类型
                return $item['parent_id'] === $pid;
            }else
            {
                return $item['parent_id'] === $pid && in_array($item['type'],[$type]) === true;
            }
            
        })->toArray();
    }

    public static function getModelByParentId($pid = 0)
    {
        return (new Model())->where(['parent_id'=>$pid])->orderBy('type','asc')->get()->toArray();
    }

    /**
     * 获取所有模型数据处理相应的格式 前端使用
     *
     * @return void
     */
    public static function allModels()
    {
        $model = new Model();
        $data = [];
        $list = $model->where(['type'=>1])->with(['relations'=>function($query){
            $query->select(['id','title','model_id','name','foreign_model_id','type'])->whereIn('type',['one','many']);
        }])->whereIn('admin_type',['system',env('APP_NAME'),''])->get()->toArray();
        foreach($list as $val)
        {
            $data[] = [
                'id'=>$val['id'],
                'columns'=>$val['columns']?json_decode($val['columns'],true):[],
                'search_columns'=>$val['search_columns']?json_decode($val['search_columns'],true):[],
                'relations'=>$val['relations']?:[]
            ];
        }
        return $data;
    }
}