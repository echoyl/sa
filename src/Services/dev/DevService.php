<?php
namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\utils\Dev;
use Echoyl\Sa\Services\dev\utils\FormItem;
use Echoyl\Sa\Services\dev\utils\SchemaDiff;
use Echoyl\Sa\Services\dev\utils\TableColumn;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class DevService
{

    var $msg = [];

    public $tpl_path = __DIR__.'/tpl/';

    public function createSchemaSql($table_name,$columns)
    {
        $table_sql = ['CREATE TABLE `la_'.$table_name.'` ('];
        $has_id = false;
        
        //$table_sql[] = '`id`  int NOT NULL AUTO_INCREMENT ,';
        foreach($columns as $val)
        {
            
            if($val['name'] == 'id')
            {
                $has_id = true;
            }
            $field_sql = $this->schemaColumnSql($val);
            $table_sql[] = $field_sql.',';
        }
        $table_sql[] = "`updated_at`  datetime DEFAULT NULL ,";
        $table_sql[] = "`created_at`  datetime DEFAULT NULL ,";
        $table_sql[] = "`displayorder`  int(11) NOT NULL DEFAULT 0 COMMENT '排序权重',";

        if($has_id)
        {
            $table_sql[] = "PRIMARY KEY (`id`))ENGINE=MyISAM;";
        }

        return $table_sql;
    }

    public function schemaColumnSql($val)
    {
        $field_sql = '';
        $default_value = $val['default']??"";
        $comment = $val['desc']??$val['title'];
        $name = $val['name'];
        $length = $val['length']??0;
        switch($val['type'])
        {
            case 'int':
                if(!$default_value)
                {
                    $default_value = 0;
                }
                if($name == 'id')
                {
                    $field_sql = "`{$name}`  int(11) NOT NULL AUTO_INCREMENT COMMENT '{$comment}'";
                }else
                {
                    $field_sql = "`{$name}`  int(11) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}'";
                }
            break;
            case 'bigint':
                if(!$default_value)
                {
                    $default_value = 0;
                }
                $field_sql = "`{$name}`  bigint NOT NULL DEFAULT {$default_value} COMMENT '{$comment}'";
            break;
            case 'vachar':
                $length = $length?:255;
                $field_sql = "`{$name}`  varchar({$length}) NOT NULL DEFAULT '{$default_value}' COMMENT '{$comment}'";
            break;
            case 'date':
                $field_sql = "`{$name}`  date DEFAULT NULL COMMENT '{$comment}'";
            break;
            case 'datetime':
                $field_sql = "`{$name}`  datetime DEFAULT NULL COMMENT '{$comment}'";
            break;
            case 'text':
                $field_sql = "`{$name}`  text NULL COMMENT '{$comment}'";
            break;
            case 'decimal':
                $field_sql = "`{$name}`  decimal(10,2) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}'";
            break;
        }
        return $field_sql;
    }

    public function createModelSchema($table_name,$columns)
    {
        
        //Schema::dropIfExists($table_name);
        
        if(!Schema::hasTable($table_name))
        {
            $table_sql = $this->createSchemaSql($table_name,$columns);
            DB::statement(implode('',$table_sql));
            $this->line('创建数据表:'.$table_name.'成功');
        }else
        {
            $table_name = 'la_'.$table_name;
            $dist_f = DB::getPdo()->query('desc '.$table_name);
            $dist_field = [];
            foreach($dist_f as $key=>$val)
            {
                $dist_field[$val[0]] = $val;
            }
            $now_fields = [];
            $sqls = [];
            foreach($columns as $column)
            {
                $field_name = $column['name'];
                $sql = $this->schemaColumnSql($column);
                if(isset($dist_field[$field_name]))
                {
                    //已存在在属性对比是否需要更新
                    $sqls[] = " MODIFY COLUMN ".$sql;
                }else
                {
                    //未存在字段则新增字段
                    $sqls[] = " ADD COLUMN ".$sql;
                }
                $now_fields[$field_name] = $column;
            }

            //DROP COLUMN `table_name`;
            $tmp = array_diff_key($dist_field,$now_fields);
            if ( !empty($tmp) )  {
                //多于的字段则删除
                foreach($tmp as $key=>$column)
                {
                    if(!isset(Utils::$title_arr[$key]))
                    {
                        //非默认字段 需要删除
                        $sqls[] = " DROP COLUMN {$key}";
                    }
                    
                }
            }
            $sqls = "ALTER TABLE `{$table_name}`\r".implode(",\r",$sqls).';';
            DB::table('dev_sqllog')->insert([
                'date'=>date("Y-m-d"),
                'sql'=>$sqls,
                'table_name'=>$table_name,
            ]);
            DB::statement($sqls);
            $this->line('数据表:'.$table_name.'已存在');
        }
        return;
    }


    public static function getPath($val, $menus,$field = 'name')
    {
        return Utils::getPath($val, $menus,$field);
    }

    public function line($msg)
    {
        $this->msg[] = $msg;
    }

    public function allModel()
    {
        static $data = [];
        if(empty($data))
        {
            $data = (new Model())->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
        }
        
        return $data;
    }

    public function allMenu()
    {
        static $data = [];
        if(empty($data))
        {
            $data = (new Menu())->with(['adminModel'])->orderBy('parent_id', 'asc')->orderBy('id', 'asc')->get()->toArray();
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
        }elseif($data['leixing'] == 'auth')
        {
            $tpl_name = 'modelAuth';
        }
        $content = file_get_contents(implode('/',[$this->tpl_path,$tpl_name.'.txt']));

        $this->line('开始生成model文件：');

        [$use_namespace,$crud_config,$parse_columns,$useModelArr] = $this->createControllerRelation($data);
        [$use_namespace2,$tpl] = $this->createModelRelation($data,$useModelArr);
        
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

        $replace_arr = [
            '/\$use_namespace\$/'=> implode("\r",$use_namespace),
            '/\$namespace\$/'=>$namespace,
            '/\$table_name\$/'=>$table_name,
            '/\$name\$/'=>$name,
            '/\$relationship\$/'=>implode("\r",$tpl),
            '/\$parse_columns\$/'=>$parse_columns,
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
        $customer_code = '';//自定义代码
        $customer_construct = '';//自定义构造函数代码
        $customer_namespace = '';
        if(file_exists($model_file_path))
        {
            $old_content = file_get_contents($model_file_path);
            $match = [];
            preg_match('/customer code start(.*)\/\/customer code end/s',$old_content,$match);
            if(!empty($match))
            {
                //已存在的文件该段不做覆盖
                $customer_code = trim($match[1]);
            }

            $match1 = [];
            preg_match('/customer construct start(.*)\/\/customer construct end/s',$old_content,$match1);
            if(!empty($match1))
            {
                //已存在的文件该段不做覆盖
                $customer_construct = trim($match1[1]);
            }

            $match2 = [];
            preg_match('/customer namespace start(.*)\/\/customer namespace end/s',$old_content,$match2);
            if(!empty($match2))
            {
                $customer_namespace = trim($match2[1]);
            }
            //d($match,$old_content);
        }

        $replace_arr = [
            '/\$namespace\$/'=>$namespace,
            '/\$modelname\$/'=>$name,
            '/\$name\$/'=>$name,
            '/\$crud_config\$/'=>implode("\r\t\t",$crud_config),
            '/\$use_namesapce\$/'=>implode("\r",$use_namespace),
            //'/\$parse_columns\$/'=>HelperService::format_var_export($parse_columns,3),
            '/\$customer_code\$/'=>$customer_code,
            '/\$customer_construct\$/'=>$customer_construct,
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
        $with_sum = $with_count = $with_columns = $search_config = [];//配置处理好后 直接返回代码了

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
        
        $with_columns_search = $with_columns_replace = [];
        $useModelArr = [];//使用过的模型数据
        if(!empty($relations))
        {
            foreach($relations as $key=>$val)
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
                if(!isset($all_relations[$val['local_key']]))
                {
                    $all_relations[$val['local_key']] = $val['name'];
                }
                

                if($val['is_with'])
                {
                    if(in_array($val['type'],['one','many']))
                    {
                        //关联模型的字段
                        
                        // $foreign_model_columns = array_merge($default_fields,collect(json_decode($foreign_model['columns'],true))->pluck('name')->toArray());
                        // //关联模型的关联模型
                        //$foreign_model_relations = $foreign_model['relations'];
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
                            $select_columns = $with_tree['columns'];
                            $inner_with = Utils::withTree($with_tree['models'],3);
                            if($inner_with)
                            {
                                $with_columns_replace[] = $inner_with;
                                $sear = 'sear_'.$key;
                                $with_columns_search[] = $sear;
                                $inner_with = '->with('.$sear.')';
                            }
                            if(!empty($select_columns))
                            {
                                $with_columns[$val['name']] = '@phpfunction($query){$query->select('.json_encode($select_columns).')'.$inner_with.';}@endphp';
                            }else
                            {
                                if($inner_with)
                                {
                                    $with_columns[$val['name']] = '@phpfunction($query){$query'.$inner_with.';}@endphp';
                                }
                            }
                            //d($with_columns[$val['name']]);
                        }else
                        {
                            //未填写表示全部
                            $with_columns[] = $val['name'];
                        }
                        
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
        }

        if(!empty($with_columns))
        {
            $crud_config[] = str_replace($with_columns_search,$with_columns_replace,'$this->with_column = '.(Dev::export($with_columns,2)).';');
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
                $default_value = $column['default']??'';
                //['name' => 'shop_id', 'type' => 'search_select', 'default' => '0','data_name'=>'shop','label'=>'name'],
                $d = [
                    'name' => $column['name'], 
                    'type' => $form_type, 
                    'default' => in_array($form_type,['select','search_select','price'])?($default_value?intval($default_value):0):$default_value,
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
                            $data_select = ["{$label} as label","{$value} as value"];
                            $d['data'] = '@php(new '.$all_models[$column['name']].'())->select('.json_encode($data_select).')->get()->toArray()@endphp';
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
                if($form_type == 'switch')
                {
                    //switch 也支持 table_menu
                    $valueEnum = [];
                    if($form_data)
                    {
                        $valueEnum = collect(explode(',',$form_data))->map(function($v,$k){
                            return ['label'=>$v,'value'=>$k];
                        })->toArray();
                    }
                   
                    // $_value = [];
                    // foreach($valueEnum as $key=>$val)
                    // {
                    //     $_value[strval($key)] = $val;
                    // }
                    //d($valueEnum);
                    
                    
                    $d['default'] = $default_value?1:0;
                    if($table_menu && !empty($valueEnum))
                    {
                        $d['with'] = true;
                        $d['data'] = $valueEnum;
                        $d['table_menu'] = true;
                    }else
                    {
                        //continue;
                    }
                }
                if($form_type == 'search_select')
                {
                    $d['data_name'] = Utils::uncamelize($all_relations[$column['name']]);
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
                if($form_type == 'selects')
                {
                    if(isset($all_models[$column['name']]))
                    {
                        $d['class'] = "@php".$all_models[$column['name']]."::class@endphp";
                        $d['with'] = true;
                    }
                    
                    
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
    
        return [$namespace_data,$crud_config,$parse_columns,$useModelArr];
    }

    public function getNamespace($foreign_model,$exist_names = [])
    {
        $foreign_model_names = array_reverse($this->getPath($foreign_model,$this->allModel()));

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

    public function getModelsTree($admin_type = '',$all_selectable = false)
    {
        $model = new Model();
        $types = $admin_type?:['system',env('APP_NAME'),''];
        $data = $model->getChild(0,$types,function($item) use($all_selectable){
            
            return [
                'id'=>$item['id'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>$item['type']?true:false,
                'selectable'=>$item['type'] || $all_selectable ? true:false,
            ];
        });
        return $data;
    }

    public function getMenusTree($admin_type = '',$all_selectable = false)
    {
        $model = new Menu();
        $types = $admin_type?:['system',env('APP_NAME'),''];
        $data = $model->getChild(0,$types,function($item) use($all_selectable){
            
            return [
                'id'=>$item['id'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>true,
                'selectable'=>true,
            ];
        });
        return $data;
    }

    public function getModelsFolderTree($admin_type = '')
    {
        $types = $admin_type?:['system',env('APP_NAME'),''];
        $model = (new Model())->whereIn('admin_type',$types)->where(['type'=>0]);
        $data = HelperService::getChild($model,function($item){
            return [
                'id'=>$item['id'],
                'title'=>$item['title'],
                'value'=>$item['id'],
                'isLeaf'=>$item['type']?true:false,
                //'selectable'=>$item['type']?true:false,
            ];
        },[['displayorder', 'desc'],['type', 'asc'],['id', 'asc']]);
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
        $key_len = count($keys);
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
            if(!is_string($formItem->data))
            {
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
            }
            
            $data[] = $d;
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
    public function modelColumn2JsonTable($model,$col)
    {
        $menus = $this->allMenu();
        $models = $this->allModel();
        $tableColumn = new TableColumn($col,$model,$menus,$models);
        return $tableColumn->data;
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
                        $model_path = array_reverse(Utils::getPath($model,$self->allModel()));
                        $_prefix = array_merge($prefix,[$menu['path']]);
                        if(count($model_path) > 1)
                        {
                            //默认所有路由都走这里 因为都有一个项目namespace
                            $name = array_pop($model_path);

                            //读取菜单的其它权限设置 单独的action方法独立写路由
                            if($menu['perms'])
                            {
                                $perms = json_decode($menu['perms'],true);
                            }else
                            {
                                $perms = false;
                            }

                            if($menu['page_type'] == 'form')
                            {
                                //如果是form直接指向控制器方法
                                $key = $menu['path'];
                                Route::group(['namespace' => implode("\\",$model_path)], function () use($name,$_prefix,$key){
                                    Route::any(implode('/',$_prefix), ucfirst($name).'Controller@'.$key);
                                });
                            }else
                            {
                                //Log::channel('daily')->info('createRoute group:',['name'=>implode('/',$_prefix),'to'=>implode('/',$model_path).'/'.ucfirst($name).'Controller',]);
                                Route::group(['namespace' => implode("\\",$model_path)], function () use($name,$_prefix,$perms){
                                    if($perms)
                                    {
                                        foreach($perms as $key=>$title)
                                        {
                                            Route::any(implode('/',array_merge($_prefix,[$key])), ucfirst($name).'Controller@'.$key);
                                        }
                                    }
                                    Route::resource(implode('/',$_prefix), ucfirst($name).'Controller');
                                });
                            }

                            
                        }else
                        {
                            if($menu['page_type'] == 'form')
                            {
                                //如果是form直接指向控制器方法
                                $key = $menu['path'];
                                Route::any(implode('/',$_prefix), ucfirst($name).'Controller@'.$key);
                            }else
                            {
                                //Log::channel('daily')->info('createRoute:',['name'=>implode('/',$_prefix),'to'=>$name]);
                                Route::resource(implode('/',$_prefix), ucfirst($name).'Controller');
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
}