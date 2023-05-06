<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Services\HelperService;

class TableColumn
{
    var $data = false;
    var $config;
    var $schema;
    var $relation;
    var $readonly = false;
    var $menus;
    var $form_type;
    var $models;
    var $key;
    var $model;

    public function __construct($config,$model,$menus,$models)
    {
        $this->config = $config;
        $this->menus = $menus;
        $this->models = $models;
        $this->model = $model;

        $key = $dataIndex = $config['key'];

        if(is_array($key))
        {
            if(count($key) == 1)
            {
                $dataIndex = $key[0];
            }
            $key = $key[0];
        }

        $this->key = $key;

        if(in_array($key,['option','coption','created_at_s','displayorder']))
        {
            $this->data = $key;
            return;
        }

        $columns = json_decode($model['columns'],true);
        $schema = Utils::arrGet($columns,'name',$key);
        $this->schema = $schema;

        $relation = Utils::arrGet($model['relations'],$schema?'local_key':'name',Utils::uncamelize($key));
        $this->relation = $relation;
        

        $title = $config['title']??'';
        //关联数据name 及 额外设置
        $extra = $config['name']??'';
        //是否出现在搜索栏
        $can_search = $config['can_search']??'';
        //是否在列表中隐藏
        $hide_in_table = $config['hide_in_table']??'';

        $d = ['dataIndex'=>$dataIndex,'title'=>$title?:($schema?$schema['title']:($relation?$relation['title']:Utils::$title_arr[$key]??''))];

        $form_type = $config['type']??'';
        if($form_type)
        {
            $d['valueType'] = $form_type;
            $this->form_type = $form_type;
        }else
        {
            $form_type = $schema['form_type']??'';
            $this->form_type = $form_type;
            if(isset(Utils::$value_type_map[$form_type]))
            {
                $form_type = Utils::$value_type_map[$form_type];
                $d['valueType'] = $form_type;
            }
        }

        //前端使用cascader自动获取key 这里后端不再检测
        // if($relation && $extra)
        // {
        //     //如果有关联数据 并且设置了读取lable字段名称
        //     $key = [Utils::uncamelize($relation['name'])];
        //     $key = array_merge($key,explode('.',$extra));
        //     $d['dataIndex'] = $key;
        // }
        //如果dataIndex的数量大于1 还是要检测下key的驼峰转换
        if(is_array($dataIndex) && count($dataIndex) > 1)
        {
            foreach($dataIndex as $i=>$index)
            {
                if($i + 1 < count($dataIndex))
                {
                    $dataIndex[$i] = Utils::uncamelize($index);
                }
            }
            $d['dataIndex'] = $dataIndex;
        }

        if(empty($can_search))
        {
            $d['search'] = false;
        }

        if(!empty($hide_in_table))
        {
            $d['hideInTable'] = true;
        }

        $this->data = $d;

        if($form_type && method_exists(self::class,$form_type))
        {
            $this->$form_type();
        }

        if($extra && !is_string($extra))
        {
            if(isset($this->data['fieldProps']))
            {
                $this->data['fieldProps'] = array_merge($this->data['fieldProps'],$extra);
            }else
            {
                $this->data['fieldProps'] = $extra;
            }
        }

        return;
    }

    public function link()
    {
        $d = $this->data;
        [$link_name] = explode('_',$this->key);
        //d($this->model['relations']);
        $with_relation = Utils::arrGet($this->model['relations'],'name',Utils::uncamelize($link_name));
        //d($with_relation);
        if($with_relation)
        {
            $menu = Utils::arrGet($this->menus,'admin_model_id',$with_relation['foreign_model_id']);
            if($menu)
            {
                $path = Utils::getPath($menu,$this->menus,'path');
                $d['fieldProps'] = [
                    'path'=>'/'.implode('/',array_reverse($path)),
                    'foreign_key'=>$d['dataIndex'][count($d['dataIndex']) - 1],
                ];
            }
        }
        $this->data = $d;
        return;
    }

     public function expre()
     {
        // $extra = $this->config['name']??'';
        // if($extra)
        // {
        //     $this->data['fieldProps'] = [
        //         'exp'=>'{{'.$extra.'}}'
        //     ];
        // }
        return;
     }

    public function select()
    {
        $d = $this->data;
        $form_data = $this->schema['form_data']??'';
        $table_menu = $this->schema['table_menu']??'';
        if($this->relation)
        {
            //关联的select 需要获取数据
            $d['requestDataName'] = $this->schema['name'].'s';
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
                if(strpos($form_data,'{'))
                {
                    $d['fieldProps']['options'] = json_decode($form_data,true);
                }else
                {
                    $d['fieldProps']['options'] = explode(',',$form_data);
                }
                // if($this->form_type == 'selects')
                // {
                //     $d['fieldProps']['mode'] = 'tags';
                // }
            }
        }
        $this->data = $d;
    }

    
    public function debounceSelect()
    {
        //搜索选项类型在table中不需要了
        unset($this->data['valueType']);
        return;
    }

    public function uploader()
    {
        $this->data['fieldProps'] = ['max'=>1];
        return;
    }

    public function switch()
    {
        $form_data = $this->schema['form_data']??'';
        //switch开关
        //是switch 或者select 需要设置数据类型为enum
        if($form_data)
        {
            $valueEnum = collect(explode(',',$form_data))->map(function($v,$k){
                return ['text' => $v, 'status' => $k == 0?'error':'success'];
            });
            $this->data['valueEnum'] = $valueEnum;
        }
        $this->data['valueType'] = 'select';
        return;
    }

    public function cascader()
    {
        //多选分类
        $d = $this->data;
        $form_data = $this->schema['form_data']??'';
        if($this->relation)
        {
            //关联的select 需要获取数据
            $d['requestDataName'] = $this->schema['name'].'s';
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
        $this->data = $d;
        return;
    }
}