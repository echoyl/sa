<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\admin\LocaleService;
use Echoyl\Sa\Services\dev\DevService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema as FacadesSchema;

class Schema
{
    var $appname = '';

    public function __construct()
    {
        $this->appname = DevService::appname();
    }

    /**
     * 获取默认字段即设置追加的字段
     *
     * @param [array] $columns
     * @param array $par
     * @return array
     */
    public function mergeSchemaColumns($columns,$par = [])
    {
        $sys_columns = [
            ['name'=>'updated_at','type'=>'datetime','desc'=>'最后更新时间'],
            ['name'=>'created_at','type'=>'datetime','desc'=>'生成时间'],
            ['name'=>'displayorder','type'=>'int','desc'=>'排序值']
        ];

        if(Arr::get($par,'soft_delete'))
        {
            $sys_columns[] = ['name'=>'deleted_at','type'=>'datetime','desc'=>'删除时间'];
        }

        if(Arr::get($par,'with_system_admin_id'))
        {
            $sys_columns[] = ['name'=>'sys_admin_id','type'=>'int','desc'=>'系统用户id'];
        }

        //检测字段是否开启多语言
        $locales = LocaleService::list();
        foreach($columns as $column)
        {
            $locale = Arr::get($column,'setting.locale');
            
            if($locale)
            {
                $name = Arr::get($column,'name');
                $title = Arr::get($column,'title');
                foreach($locales as $lang)
                {
                    $lang_columns = $column;
                    $lang_columns['name'] = implode('_',[$name,$lang['name']]);
                    $lang_columns['title'] = implode('-',[$title,$lang['title']]);
                    $columns[] = $lang_columns;
                }
            }
        }
        return collect(array_merge($sys_columns,$columns))->sortBy('name')->toArray();
    }

    /**
     * 生成单个表的sql
     *
     * @param [type] $table_name
     * @param [type] $columns
     * @param array $par
     * @return array
     */
    public function createSchemaSql($table_name,$columns,$par = [])
    {
        $table_sql = ['CREATE TABLE `la_'.$table_name.'` ('];
        $has_id = false;
        
        //$table_sql[] = '`id`  int NOT NULL AUTO_INCREMENT ,';

        $columns = $this->mergeSchemaColumns($columns,$par);

        foreach($columns as $val)
        {
            
            if($val['name'] == 'id')
            {
                $has_id = true;
            }
            $field_sql = $this->schemaColumnSql($val);
            $table_sql[] = $field_sql.',';
        }

        if($has_id)
        {
            $table_sql[] = "PRIMARY KEY (`id`))ENGINE=MyISAM;";
        }

        return $table_sql;
    }

    /**
     * 生成单个字段的sql
     *
     * @param [单个字段信息] $val
     * @return void
     */
    public function schemaColumnSql($val)
    {
        $field_sql = '';
        $default_value = Arr::get($val,'default','');
        $comment = $val['desc']??$val['title'];
        $name = $val['name'];
        $length = $val['length']??0;
        $setting = Arr::get($val,'setting',[]);
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
            case 'varchar':
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
            case 'longtext':
                $field_sql = "`{$name}`  longtext NULL COMMENT '{$comment}'";
            break;
            case 'decimal':
                $field_sql = "`{$name}`  decimal(10,2) NOT NULL DEFAULT {$default_value} COMMENT '{$comment}'";
            break;
            case 'enum':
                //读取预设的数据
                //
                $json = Arr::get($setting,'json',[]);
                $enums = collect($json)->map(function($v){
                    return $v['id'];
                })->toArray();
                if(!empty($enums))
                {
                    if(!in_array($default_value,$enums))
                    {
                        $default_value = $enums[0];
                    }
                    $field_sql = "`{$name}` enum('".implode("','",$enums)."') NOT NULL DEFAULT '{$default_value}' COMMENT '{$comment}' FIRST";
                }else
                {
                    $field_sql = "`{$name}` enum NULL DEFAULT NULL COMMENT '{$comment}' FIRST";
                }
            break;
        }
        return $field_sql;
    }

    /**
     * 通过table的信息生成json字段信息
     *
     * @param [type] $name
     * @param [type] $parent_id
     * @return void
     */
    public function tabel2SchemaJson($name,$parent_id)
    {
        //先直接检测表是否存在
        $table_name = '';
        $appname = $this->appname;
        if(FacadesSchema::hasTable($name))
        {
            $table_name = $name;
        }elseif(FacadesSchema::hasTable($appname.'_'.$name))
        {
            $table_name = $appname.'_'.$name;
        }else
        {
            $all = $this->allModel(true);
        
            $parent_model = (new Model())->where(['id'=>$parent_id])->first();
            $table_name = [];
            if($parent_model)
            {
                $table_name = array_reverse(DevService::getPath($parent_model, $all));
                $table_name[] = $name;
            }else
            {
                $table_name = [$appname,$name];
            }
    
            $table_name = implode('_',$table_name);

            if(!FacadesSchema::hasTable($table_name))
            {
                return [1,'数据表未创建,请先创建后重试'];
            }
            
        }

        $table_name = 'la_'.$table_name;
        
        $dist_f = DB::getPdo()->query('SHOW FULL COLUMNS from '.$table_name);

        $items = [];

        foreach($dist_f as $val)
        {
            $field = $val['Field'];
            if(array_key_exists($field,Utils::$title_arr))
            {
                continue;
            }

            $type = $val['Type'];
            $comment = $val['Comment'];
            //长度大于5个字符的直接使用Field
            if(!$comment || mb_strlen($comment) > 5)
            {
                $title = $field;
            }else
            {
                $title = $comment;
                $comment = '';//重置
            }

            $item = [
                "title"=> $title,
                "name"=> $field,
                'type'=>$type
            ];

            if($comment)
            {
                $item['desc'] = $comment;
            }

            
            if(strpos($type,'varchar') !== false)
            {
                $item['type'] = 'varchar';
                $length = substr($type,8,-1);
                if($length != 255)
                {
                    $item['length'] = $length;
                }
            }
            if(strpos($type,'enum') !== false)
            {
                $item['type'] = 'enum';
                preg_match('/\((.+)\)/',$type,$match);
                if($match && isset($match[1]))
                {
                    $enums = explode(',',str_replace("'",'',$match[1]));
                    $item['setting'] = [
                        'json'=>collect($enums)->map(function($v){
                            return ['id'=>$v,'title'=>$v];
                        })->toArray()
                    ];
                }
            }
            if($val['Default'])
            {
                $item['default'] = $val['Default'];
            }
            

            $items[] = $item;
        }

        return [0,$items];

    }

    public function createModelSchema($model)
    {
        $all = $this->allModel(true);

        $table_name = implode('_', array_reverse(DevService::getPath($model, $all)));

        $columns = [];
        if ($model['columns']) {
            $columns = json_decode($model['columns'], true);
        }
        $setting = $model['setting']?json_decode($model['setting'],true):[];
        //Schema::dropIfExists($table_name);
        
        if(!FacadesSchema::hasTable($table_name))
        {
            $table_sql = $this->createSchemaSql($table_name,$columns,$setting);
            DB::statement(implode('',$table_sql));
            //$this->line('创建数据表:'.$table_name.'成功');
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

            $columns = $this->mergeSchemaColumns($columns,$setting);

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
                //多余的字段则删除
                foreach($tmp as $key=>$column)
                {
                    $sqls[] = " DROP COLUMN `{$key}`";
                }
            }
            $sqls = "ALTER TABLE `{$table_name}`\r".implode(",\r",$sqls).';';
            DB::table('dev_sqllog')->insert([
                'date'=>date("Y-m-d"),
                'sql'=>$sqls,
                'table_name'=>$table_name,
            ]);
            DB::statement($sqls);
            //$this->line('数据表:'.$table_name.'已存在');
        }
        return;
    }

    public function allModel($flush = false)
    {
        return (new DevService)->allModel($flush);
    }
}