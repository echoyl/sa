<?php
namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DevService2
{

    var $msg = [];
    var $value_type = [
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
    ];

    var $title_arr = [
        'created_at'=>'创建时间',
        'updated_at'=>'最后更新时间'
    ];

    public $tpl_path = __DIR__.'/tpl/';
    

    public function createModelSchema($table_name,$columns)
    {
        $table_sql = ['CREATE TABLE `la_'.$table_name.'` ('];
        $has_id = false;
        //$table_sql[] = '`id`  int NOT NULL AUTO_INCREMENT ,';
        foreach($columns as $val)
        {
            $field_sql = '';
            $default_value = $val['default']??"";
            $comment = $val['desc']??$val['title'];
            $name = $val['name'];
            switch($val['type'])
            {
                
                case 'int':
                    if(!$default_value)
                    {
                        $default_value = 0;
                    }
                    if($name == 'id')
                    {
                        $has_id = true;
                        $field_sql = "`{$val['name']}`  int(11) NOT NULL AUTO_INCREMENT COMMENT '{$comment}',";
                    }else
                    {
                        $field_sql = "`{$val['name']}`  int(11) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}',";
                    }
                break;
                case 'vachar':
                    $field_sql = "`{$val['name']}`  varchar(255) NOT NULL DEFAULT '{$default_value}' COMMENT '{$comment}',";
                break;
                case 'datetime':
                    $field_sql = "`{$val['name']}`  datetime DEFAULT NULL COMMENT '{$comment}',";
                break;
                case 'text':
                    $field_sql = "`{$val['name']}`  text NULL COMMENT '{$comment}',";
                break;
                case 'decimal':
                    $field_sql = "`{$val['name']}`  decimal(10,2) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}',";
                break;
            }
            
            $table_sql[] = $field_sql;
        }
        $table_sql[] = "`updated_at`  datetime DEFAULT NULL ,";
        $table_sql[] = "`created_at`  datetime DEFAULT NULL ,";
        $table_sql[] = "`displayorder`  int(11) NOT NULL DEFAULT 0 COMMENT '排序权重',";

        if($has_id)
        {
            $table_sql[] = "PRIMARY KEY (`id`))ENGINE=MyISAM;";
        }
        Schema::dropIfExists($table_name);
        if(!Schema::hasTable($table_name))
        {
            DB::statement(implode('',$table_sql));
            $this->line('创建数据表:'.$table_name.'成功');
        }else
        {
            $this->line('数据表:'.$table_name.'已存在');
        }
        return;
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

    public function line($msg)
    {
        $this->msg[] = $msg;
    }

    public function allData($model)
    {
        static $data = [];
        if(empty($data))
        {
            $data = $model->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
        }
        
        return $data;
    }

    /**
     * 生成模型关系的use namespace 和 代码定义部分
     *
     * @param [type] $model
     * @return void
     */
    public function createModelRelation($model)
    {
        $model_id = $model['id'];
        $relations = (new Relation())->where(['model_id'=>$model_id])->with(['foreignModel'])->get()->toArray();
        $namespace_data = [];
        $hasone_data = [];
        if(empty($relations))
        {
            return [$namespace_data,$hasone_data];
        }

        $hasone_tpl ="
    public function _name()
    {
        return \$this->has_type(_modelName::class,'_foreignKey','_localKey');
    }";
        
        $has_model = [ucfirst($model['name'])];
        $useModelArr = [];//使用过的模型数据
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

            $hasone_data[] = str_replace([
                '_name',
                '_modelName',
                '_foreignKey',
                '_localKey',
                '_type'
            ],[
                $val['name'],
                $foreign_model_name,
                $val['foreign_key'],
                $val['local_key'],
                ucfirst($val['type'])
            ],$hasone_tpl);
        }
        return [$namespace_data,$hasone_data];
    }

    /**
     * 生成模型文件
     *
     * @param [array] $names 文件的路径数组
     * @param [object] $data 模型的数据信息
     * @return void
     */
    public function createModelFile($names,$data)
    {
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
        }
        $content = file_get_contents(implode('/',[$this->tpl_path,$tpl_name.'.txt']));

        $this->line('开始生成model文件：');

        [$use_namespace,$crud_config,$parse_columns] = $this->createControllerRelation($data);
        [$use_namespace2,$tpl] = $this->createModelRelation($data);
        
        $use_namespace = array_unique(array_merge($use_namespace,$use_namespace2));
    
        //d($use_namespace,$tpl);

        $replace_arr = [
            '/\$use_namespace\$/'=> implode("\r",$use_namespace),
            '/\$namespace\$/'=>$namespace,
            '/\$table_name\$/'=>$table_name,
            '/\$name\$/'=>$name,
            '/\$relationship\$/'=>implode("\r",$tpl),
            '/\$parse_columns\$/'=>HelperService::format_var_export($parse_columns,3),
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

    public function createControllerFile($names,$data)
    {
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
        $customer_code = '';
        if(file_exists($model_file_path))
        {
            $old_content = file_get_contents($model_file_path);
            $match = [];
            preg_match('/customer code start(.*)\/\/customer code end/s',$old_content,$match);
            if(!empty($match))
            {
                $customer_code = trim($match[1]);
            }
            //d($match,$old_content);
        }

        $replace_arr = [
            '/\$namespace\$/'=>$namespace,
            '/\$modelname\$/'=>$name,
            '/\$name\$/'=>$name,
            '/\$crud_config\$/'=>implode("\r\t",$crud_config),
            '/\$use_namesapce\$/'=>implode("\r",$use_namespace),
            '/\$parse_columns\$/'=>HelperService::format_var_export($parse_columns,3),
            '/\$customer_code\$/'=>$customer_code,
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
        $with_sum = $with_count = $with_columns = $search_config = [];//配置处理好后 直接返回代码了

        $relations = (new Relation())->where(['model_id'=>$model_id])->with(['foreignModel'])->get()->toArray();

        //循环检测列中支持搜索的字段
        $columns = $model['search_columns']?json_decode($model['search_columns'],true):[];
        foreach($columns as $val)
        {
            $search_config[] = ['name'=>$val['name'],'columns'=>$val['columns'],'where_type'=>$val['type']];;
        }

        

        $has_model = [ucfirst($model['name'])];
        $all_models = [$model['name']=>$model['name']];
        $all_relations = [];
        
        if(!empty($relations))
        {
            $useModelArr = [];//使用过的模型数据
            foreach($relations as $val)
            {
                
    
                if($val['can_search'])
                {
                    //这里的关联名称需要转化小驼峰->下划线模式
                    $search_config[] = ['name'=>$val['name'],'columns'=>explode(',',$val['search_columns']),'type'=>'has'];
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
                $all_relations[$val['local_key']] = $val['name'];

                if($val['is_with'])
                {
                    if(in_array($val['type'],['one','many']))
                    {
                        $with_columns[] = $val['name'];
                    }
                    
                    if($val['type'] == 'one')
                    {
                        //1对1时候 还需要关联对接 转换关联数据
                        $parse_columns[] = [
                            'name'=>$val['name'],
                            'type'=>'model',
                            'class'=>'@php'.$f_model_name.'::class@endphp',
                        ];
    
                    }
                }

                

            }
        }

        if(!empty($with_columns))
        {
            $crud_config[] = 'var $with_column = '.(json_encode($with_columns)).';';
        }
        if(!empty($search_config))
        {
            $crud_config[] = 'var $search_config = '.(HelperService::format_var_export($search_config,2)).';';
        }
        if(!empty($with_sum))
        {
            //d(json_encode($with_sum));
            $crud_config[] = 'var $with_sum = '.(HelperService::format_var_export($with_sum,2)).';';
        }
        if(!empty($with_count))
        {
            $crud_config[] = 'var $with_count = '.(json_encode($with_count)).';';
        }


        

        //生成parse_columns 数组
        if($model['columns'])
        {
            $columns = json_decode($model['columns'],true);
            foreach($columns as $column)
            {
                if(!isset($column['form_type']) || in_array($column['form_type'],['textarea','dateTime','tinyEditor','datetime']))
                {
                    //不存在type 或者一些不需要parse的字段 直接过滤
                    continue;
                }
                $form_data = $column['form_data']??'';
                $form_type = $column['form_type'];
                //['name' => 'shop_id', 'type' => 'search_select', 'default' => '0','data_name'=>'shop','label'=>'name'],
                $d = [
                    'name' => $column['name'], 
                    'type' => $form_type, 
                    'default' => in_array($form_type,['select','search_select'])?0:'',
                ];
                $table_menu = isset($column['table_menu']) && $column['table_menu'];
                if($form_type == 'select')
                {
                    if(isset($all_models[$column['name']]))
                    {
                        
                        //如果是select 且设置了tabel menu，那么form_data设置的label 和value 

                        if($table_menu && $form_data)
                        {
                            [$label,$value] = explode(',',$form_data);
                            $d['data'] = "@php(new ".$all_models[$column['name']]."())->select(['{$label} as label','{$value} as value'])->get()->toArray()@endphp";
                        }else
                        {
                            $d['data'] = "@php(new ".$all_models[$column['name']]."())->get()->toArray()@endphp";
                        }

                    }else
                    {
                        if($form_data)
                        {
                            //d(json_decode($column['form_data'],true));
                            $d['data'] = json_decode($form_data,true);
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
                if($form_type == 'switch'  && $form_data)
                {
                    //switch 也支持 table_menu
                    $valueEnum = collect(explode(',',$form_data))->map(function($v,$k){
                        return ['label'=>$v,'value'=>$k];
                    })->toArray();
                    // $_value = [];
                    // foreach($valueEnum as $key=>$val)
                    // {
                    //     $_value[strval($key)] = $val;
                    // }
                    //d($valueEnum);
                    $d['data'] = $valueEnum;
                    $d['with'] = true;
                    $d['default'] = 1;
                    if($table_menu)
                    {
                        $d['table_menu'] = true;
                    }else
                    {
                        continue;
                    }
                }
                if($form_type == 'search_select')
                {
                    $d['data_name'] = $this->uncamelize($all_relations[$column['name']]);
                    $fieldNames = explode(',',$column['form_data']);
                    $d['label'] = $fieldNames[0];
                    if(isset($fieldNames[1]))
                    {
                        $d['value'] = $fieldNames[1];
                    }
                }
                if($form_type == 'cascaders')
                {
                    $d['class'] = "@php".$all_models[$column['name']]."::class@endphp";
                    $d['with'] = true;
                }
                //省市区选择器
                if($form_type == 'pca')
                {
                    $d['default'] = "__unset";
                    if(isset($column['form_data']))
                    {
                        $d['level'] = intval($column['form_data']);
                    }
                    
                }
                $parse_columns[] = $d;
            }
        }
        


        return [$namespace_data,$crud_config,$parse_columns];
    }

    public function getNamespace($foreign_model,$exist_names = [])
    {
        $foreign_model_names = array_reverse($this->getPath($foreign_model,$this->allData(new Model())));

        $foreign_model_name = ucfirst(array_pop($foreign_model_names));

        $namespace_prefix = 'use App\Models';
        if($foreign_model['admin_type'] == 'system')
        {
            //系统模型需要获取单独的namespace 前缀
            $namespace_prefix = 'use Echoyl\Sa\Models';
        }
        if(!empty($foreign_model_names))
        {
            $namespace_prefix .= '\\'.implode('\\',$foreign_model_names);
        }
        $namespace_prefix .= '\\'.$foreign_model_name;

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
        return [$namespace_prefix,$foreign_model_name];
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

    public function uncamelize($camelCaps,$separator='_')
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', "$1" . $separator . "$2", $camelCaps));
    }

    public function getModelsTree()
    {
        $model = new Model();
        $data = $model->getChild(0,['system',env('APP_NAME'),''],function($item){
            
            return [
                'id'=>$item['id'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>$item['type']?true:false,
                'selectable'=>$item['type']?true:false,
            ];
        });
        return $data;
    }

    /**
     * 生成model的某个字段的json 前端配置
     *
     * @param [type] $model
     * @param [type] $key
     * @return array
     */
    public function modelColumn2JsonForm($model,$keys)
    {
        $columns = json_decode($model['columns'],true);

        $columns[] = [
            'name'=>'created_at',
            'title'=>'创建时间',
            'form_type'=>'datetime'
        ];

        $data = [];
        $value_type = $this->value_type;
        $key_len = count($keys);
        foreach($keys as $row)
        {
            $key = $row['key'];
            
            if(in_array($key,['id','parent_id','created_at_s','displayorder']))
            {
                $data[] = $key;
                continue;
            }
            
            $column = collect($columns)->first(function($item) use($key){
                return $item['name'] == $key;
            });

            $relation = $this->columnHasRelations($model['relations'],$key,$column?'local_key':'name');

            $title = $row['title']??'';
            $readonly = $row['readonly']??'';
            $set_label = $row['label']??'';

            $d = ['dataIndex'=>$key,'title'=>$title?:($column?$column['title']:$relation['title'])];

            if($readonly)
            {
                $d['readonly'] = true;
            }

            $form_type = $row['type']??'';
            if($form_type)
            {
                $d['valueType'] = $form_type;
            }

            if(!$column)
            {
                //如果选择的不是数据表字段，因为可以选择关联的字段
                if(!$relation)
                {
                    //如果连关联字段都没有 那么直接跳过
                    continue;
                }
                if($form_type)
                {
                    $fieldProps = [];
                    if($readonly)
                    {
                        unset($d['readonly']);
                        $fieldProps['readonly'] = true;
                        $d['fieldProps'] = ['readonly'=>true];
                    }
                    //如果是saFormTable 表单中的table 需要读取该关联模型所对应的第一个菜单的所形成的地址，这样组件可以在页面中根据这个path获取该页面的配置参数信息
                    if($relation['foreign_model']['menu'])
                    {
                        $path = array_reverse(self::getPath($relation['foreign_model']['menu'],(new Menu())->get()->toArray(),'path'));
                        $fieldProps['path'] = implode('/',$path);
                        $fieldProps['foreign_key'] = $relation['foreign_key'];
                        $fieldProps['local_key'] = $relation['local_key'];
                    }
                    
                    if(!empty($fieldProps))
                    {
                        $d['fieldProps'] = $fieldProps;
                    }
                }

                

                $data[] = $d;
                continue;

            }

            if($key_len == 2)
            {
                $d['width'] = 'md';
            }elseif($key_len == 3)
            {
                $d['width'] = 'sm';
            }elseif($key_len > 4)
            {
                $d['width'] = 'xs';
            }

            $form_type = $column['form_type']??'';
            $table_menu = $column['table_menu']??'';
            $form_data = $column['form_data']??'';

            if(isset($value_type[$form_type]))
            {
                $d['valueType'] = $value_type[$form_type];
            }
            

            if($relation && $set_label)
            {
                //如果有关联数据 并且设置了读取lable字段名称
                $key = [$this->uncamelize($relation['name'])];
                $key = array_merge($key,explode('.',$set_label));
                $d['dataIndex'] = $key;
            }

            if($form_type == 'select' || $form_type == 'selects')
            {
                if($relation)
                {
                    $d['requestDataName'] = $column['name'].'s';
                    if($form_data)
                    {
                        [$label,$value] = explode(',',$form_data);
                        if(!$table_menu)
                        {
                            $d['fieldProps'] = ['fieldNames'=>[
                                'label'=>$label,'value'=>$value
                            ]];
                        }
                    }
                }else
                {
                    if($form_data)
                    {
                        if(strpos($form_data,'{'))
                        {
                            $d['fieldProps']['options'] = json_decode($form_data,true);
                        }else
                        {
                            $d['fieldProps']['options'] = explode(',',$form_data);
                        }
                        if($form_type == 'selects')
                        {
                            $d['fieldProps']['mode'] = 'tags';
                        }
                    }
                }
                if($readonly)
                {
                    //只读的话 删除valueType 直接显示数据了
                    unset($d['valueType']);
                    //$d['dataIndex'] = [$relation['name'],$label];
                }
            }elseif($form_type == 'search_select')
            {
                //输入搜索select
                if($form_data)
                {
                    [$label,$value] = explode(',',$form_data);
                }
                if($readonly)
                {
                    unset($d['valueType']);
                    //$d['dataIndex'] = [$relation['name'],$label];
                }else
                {
                    $d['fieldProps'] = [];
                    if($relation && $relation['foreign_model'])
                    {
                        $path = array_reverse($this->getPath($relation['foreign_model'],$this->allData(new Model())));
                        $d['fieldProps']['fetchOptions'] = implode('/',$path);
                    }
                    if($form_data)
                    {
                        $d['fieldProps']['fieldNames'] = ['label'=>$label,'value'=>$value];
                    }
                }
                
            }elseif($form_type == 'image')
            {
                //图片上传
                if($form_data)
                {
                    $d['fieldProps'] = ['max'=>intval($form_data)];
                }
            }elseif($form_type == 'switch')
            {
                //switch开关
                if($form_data)
                {
                    [$label,$value] = explode(',',$form_data);
                    $d['fieldProps'] = [
                        "checkedChildren"=>$value,
                        "unCheckedChildren"=>$label,
                        "defaultChecked"=>true
                    ];
                }
            }elseif($form_type == 'cascaders')
            {
                //多选分类
                $d['requestDataName'] = $column['name'].'s';
                $d['fieldProps'] = [
                    'placeholder'=>'请选择'.$column['title'],
                    'multiple'=>true,
                    'showCheckedStrategy'=>'SHOW_CHILD'
                ];
            }elseif($form_type == 'pca')
            {
                //省市区选择

                //$d['requestDataName'] = $column['name'].'s';
                if($form_data)
                {
                    $d['fieldProps'] = [
                        'level'=>intval($form_data)
                    ];
                }
                
            }

            $data[] = $d;
        }

        return $data;
    }

    public function modelColumn2JsonTable($model,$col)
    {
        $key = $col['key'];

        if(is_array($key))
        {
            $key = $key[0];
        }

        if(in_array($key,['option','coption','created_at_s','displayorder']))
        {
            return $key;
        }

        $value_type = $this->value_type;

        $title = $col['title']??'';
        $columns = json_decode($model['columns'],true);
        $column = collect($columns)->first(function($item) use($key){
            return $item['name'] == $key;
        });

        $relation = $this->columnHasRelations($model['relations'],$key,$column?'local_key':'name');

        $d = ['dataIndex'=>$key,'title'=>$title?:($column?$column['title']:($this->title_arr[$key]??''))];

        //关联数据name 及 额外设置
        $extra = $col['name']??'';

        //根据每行的设置定义部分参数
        if(isset($col['type']))
        {
            $d['valueType'] = $col['type'];
            if($d['valueType'] == 'link')
            {
                [$link_name] = explode('_',$key);
                $with_relation = $this->columnHasRelations($model['relations'],$link_name,'name');
                if($with_relation)
                {
                    $menu = (new Menu())->where(['admin_model_id'=>$with_relation['foreign_model_id']])->first();
                    if($menu)
                    {
                        $path = self::getPath($menu,(new Menu)->get(),'path');
                        $d['fieldProps'] = [
                            'path'=>'/'.implode('/',array_reverse($path)),
                            'foreign_key'=>$with_relation['foreign_key'],
                            'local_key'=>$with_relation['local_key'],
                        ];
                    }
                }
                
            }elseif($d['valueType'] == 'expre' && $extra)
            {
                $d['fieldProps'] = [
                    'exp'=>'{{'.$extra.'}}'
                ];
            }
        }

        if(isset($col['can_search']) && $col['can_search'])
        {

        }else
        {
            $d['search'] = false;
        }

        if(isset($col['hide_in_table']) && $col['hide_in_table'])
        {
            $d['hideInTable'] = true;
        }

        

        if($extra && $relation)
        {
            //有关联模型的是才会解析dataIndex 
            $key = [$this->uncamelize($relation['name'])];
            $d['dataIndex'] = array_merge($key,explode('.',$extra));
        }

        if(!$column)
        {
            //选择的是关联数据 而不是选择表中的某个字段
            return $d;
        }

        

        $form_type = $column['form_type']??'';
        $form_data = $column['form_data']??'';
        $table_menu = $column['table_menu']??'';

        if(isset($value_type[$form_type]) && !isset($d['valueType']))
        {
            $d['valueType'] = $value_type[$form_type];
        }

        //搜索选项类型在table中不需要了
        if($form_type == 'search_select')
        {
            unset($d['valueType']);
        }
        

        //是switch 或者select 需要设置数据类型为enum
        if($form_type == 'switch' && $form_data)
        {
            $d['valueType'] = 'select';
            $valueEnum = collect(explode(',',$form_data))->map(function($v,$k){
                return ['text' => $v, 'status' => $k == 0?'error':'success'];
            });
            // $_value = [];
            // foreach($valueEnum as $key=>$val)
            // {
            //     $_value[strval($key)] = $val;
            // }
            $d['valueEnum'] = $valueEnum;
        }

        if($form_type == 'select' )
        {
            if($relation)
            {
                //关联的select 需要获取数据
                $d['requestDataName'] = $column['name'].'s';
                if($form_data && !$table_menu)
                {
                    //如果设置该列为table_menu 则不需要设置fieldNames，使用默认即可
                    [$label,$value] = explode(',',$form_data);
                    $d['fieldProps'] = ['fieldNames'=>[
                        'label'=>$label,'value'=>$value
                    ]];
                }
            }else
            {
                //非关联的话 手动设置数据源
                if($form_data)
                {
                    $d['fieldProps'] = [
                        'options'=>json_decode($form_data,true)
                    ];
                }
            }
        }

        

        if($form_type == 'cascaders')
        {
            if($relation)
            {
                //关联的select 需要获取数据
                $d['requestDataName'] = $column['name'].'s';
                // if(isset($column['form_data']))
                // {
                //     [$label,$value] = explode(',',$column['form_data']);
                //     $d['fieldProps'] = ['fieldNames'=>[
                //         'label'=>$label,'value'=>$value
                //     ]];
                // }
            }else
            {
                //非关联的话 手动设置数据源
                if($form_data)
                {
                    $d['fieldProps'] = [
                        'options'=>json_decode($form_data,true)
                    ];
                }
            }
        }

        if($form_type == 'image')
        {
            //图片的话 列表中只显示一张
            $d['fieldProps'] = [
                'max'=>1
            ];
        }

        return $d;
    }

    public function columnHasRelations($relations,$key,$key_name = 'local_key')
    {
        if(!$relations)
        {
            return false;
        }
        $key = $this->uncamelize($key);
        return collect($relations)->first(function($item) use($key,$key_name){
            return $item[$key_name] == $key;
        });
    }

}