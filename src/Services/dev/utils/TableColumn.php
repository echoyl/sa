<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;
use stdClass;

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
    var $props;//config中的props; 属性集中到这个字段中

    public function __construct($config,$model,$menus,$models)
    {
        $this->config = $config;
        $this->menus = $menus;
        $this->models = $models;
        $this->model = $model;

        //关联数据name 及 额外设置 //后面将extra相当于fieldProps 转移至props.fieldProps中 兼容之前的设置
        $props = $config['props']??'';
        $this->props = $props;

        $key = $dataIndex = $config['key']??'';

        if(isset($props['dataIndex']) && $props['dataIndex'])
        {
            $key = $dataIndex = $props['dataIndex'];
        }

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
        
        
        $p_title = $props['title']??'';
        $title = $config['title']??'';
        $title = $p_title?:$title;
        //$extra = $config['name']??'';
        $fieldProps = $props['fieldProps']??'';
        $formItemProps = $props['formItemProps']??'';

        //tooltip提示
        $tooltip = $props['tooltip']??'';
        //是否排序
        $sort = $config['sort']??'';
        //是否出现在搜索栏
        $can_search = $config['can_search']??'';
        //是否在列表中隐藏
        $hide_in_table = $config['hide_in_table']??'';
        //ellipsis
        $ellipsis = $props['ellipsis']??'';
        //copyable
        $copyable = $props['copyable']??'';
        //是否设定列宽
        $width = $props['width']??'';
        //$width = $config['width']??$p_width;
        $relation_title = '';
        if($relation && is_array($dataIndex))
        {
            $_relation_title = [$relation['title']];
            $foreign_model_columns = json_decode($relation['foreign_model']['columns'],true);
            $field = Utils::arrGet($foreign_model_columns,'name',$dataIndex[1]);
            if($field && $field['title'])
            {
                $_relation_title[] = $field['title'];
            }
            $relation_title = implode(' - ',$_relation_title);
        }

        $d = ['dataIndex'=>$dataIndex,'title'=>$title?:($schema?$schema['title']:($relation_title?:Utils::$title_arr[$key]??''))];
        if($width)
        {
            $d['width'] = is_numeric($width)?intval($width):$width;
        }

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

        if(!empty($tooltip))
        {
            $d['tooltip'] = $tooltip;
        }

        if(!empty($sort))
        {
            $d['sort'] = true;
        }
        if($ellipsis)
        {
            $d['ellipsis'] = true;
        }
        if($copyable)
        {
            $d['copyable'] = true;
        }

        $this->data = $d;

        if($form_type && method_exists(self::class,$form_type))
        {
            $this->$form_type();
        }

        if($fieldProps && !is_string($fieldProps))
        {
            if(isset($this->data['fieldProps']))
            {
                $this->data['fieldProps'] = array_merge($this->data['fieldProps'],$fieldProps);
            }else
            {
                $this->data['fieldProps'] = $fieldProps;
            }
        }
        if($formItemProps && !is_string($formItemProps))
        {
            $this->data['formItemProps'] = $formItemProps;
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
                if(is_array($d['dataIndex']))
                {
                    $foreign_key = $d['dataIndex'][count($d['dataIndex']) - 1];
                    $local_key = '';
                }else
                {
                    $foreign_key = $with_relation['foreign_key'];
                    $local_key = 'id';
                }
                $d['fieldProps'] = [
                    'path'=>'/'.implode('/',array_reverse($path)),
                    'foreign_key'=>$foreign_key,
                    'local_key'=>$local_key,
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

    public function toolbar()
    {
        $this->customerColumn();
        return;
    }

    /**
     * 自定义列
     * 将items 的值传入fieldProps
     * 
     * @return void
     */
    public function customerColumn()
    {
        $props = $this->props;
        if(!$props || !isset($props['items']) || !$props['items'])return;
        $items = $props['items'];

        //检测是否有modalTable 有的话通过关联数据设置属性值
        foreach($items as $key=>$item)
        {
            $action = Arr::get($item,'action');
            $fieldProps = Arr::get($item,'fieldProps.value',[]);
            if($action == 'modalTable' || $action == 'drawerTable')
            {
                $model = Arr::get($item,'modal.model');
                $relation = Utils::arrGet($this->model['relations'],'name',Utils::uncamelize($model));
                if($relation['foreign_model']['menu'])
                {
                    $path = array_reverse(Utils::getPath($relation['foreign_model']['menu'],$this->menus,'path'));
                    $fieldProps['path'] = implode('/',$path);
                    $fieldProps['foreign_key'] = $relation['foreign_key'];
                    $fieldProps['local_key'] = $relation['local_key'];
                    $fieldProps['name'] = $relation['title'];
                    $item['fieldProps'] = $fieldProps;
                }
                $items[$key] = $item;
            }
        }
        $this->data['readonly'] = true;
        $this->data['fieldProps'] = ['items'=>$items];
        return;
    }


    public function select()
    {
        $d = $this->data;
        $setting = $this->schema['setting']??[];
        $label = $setting['label']??'';
        $value = $setting['value']??'';
        $table_menu = $this->schema['table_menu']??'';
        if($this->relation)
        {
            //关联的select 需要获取数据
            $d['requestDataName'] = $this->schema['name'].'s';
            $d['fieldProps'] = [];
            if(!$value || !$label)
            {
                if($this->schema['form_type'] == 'select')
                {
                    $label = 'title';
                    $value = 'id';
                }
            }
            if(!$table_menu && $label)
            {
                //如果设置该列为table_menu 则不需要设置fieldNames，使用默认即可
                $d['fieldProps']['fieldNames'] = [
                    'label'=>$label,'value'=>$value
                ];
            }
            if($this->form_type == 'selects')
            {
                $d['fieldProps']['mode'] = 'tags';
            }
        }else
        {
            //非关联的话 手动设置数据源
            if(isset($setting['json']) && $setting['json'])
            {
                if(is_string($setting['json']))
                {
                    $d['fieldProps']['options'] = json_decode($setting['json'],true);
                }else
                {
                    $d['fieldProps']['options'] = $setting['json'];
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
        $setting = $this->schema['setting']??[];
        $open = $setting['open']??'开';
        $close = $setting['close']??'关';
        //switch开关
        //是switch 或者select 需要设置数据类型为enum
        //$valueEnum = new stdClass;
        $valueEnum = [
            ['text' => $close, 'status' => 'error'],
            ['text' => $open, 'status' => 'success']
        ];
        $this->data['valueEnum'] = $valueEnum;
        //列表显示 需要设置类型为 select
        //如果key是state  预设值列表可操作
        if($this->key == 'state')
        {
            $this->data['valueType'] = 'customerColumn';
            $this->data['readonly'] = true;
            $this->data['fieldProps'] = [
                'items'=>[
                    [
                        "domtype"=>"text",
                        "action"=> "dropdown",
                        "request"=> [
                          "url"=> '{{url}}',
                          "modelName"=> "state",
                          "fieldNames"=> "value,label",
                          "data"=> [
                            "actype"=> "state"
                          ]
                        ]
                    ]
                ]
            ];

        }else
        {
            $this->data['valueType'] = 'select';
        }
        
        return;
    }

    public function cascader()
    {
        //多选分类
        $d = $this->data;
        $setting = $this->schema['setting']??[];
        if($this->relation)
        {
            //关联的select 需要获取数据
            $d['requestDataName'] = $this->schema['name'].'s';
            $d['fieldProps'] = [
                'changeOnSelect'=>true
            ];
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
            if(isset($setting['json']))
            {
                $d['fieldProps'] = [
                    'options'=>json_decode($setting['json'],true),
                    'changeOnSelect'=>true
                ];
            }
        }
        $this->data = $d;
        return;
    }
    public function pca()
    {
        $this->data['fieldProps'] = [
            'changeOnSelect'=>true
        ];
        return;
    }
}