<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Model;
use Illuminate\Support\Arr;

class FormItem
{
    var $data = false;
    var $config;
    var $schema;
    var $relation;
    var $model;
    var $readonly = false;
    var $menus;
    var $form_type;
    var $models;
    var $props;//config中的props; 属性集中到这个字段中

    public function __construct($config,$model,$menus,$models)
    {
        $this->config = $config;
        $this->menus = $menus;
        $this->models = $models;
        $this->model = $model;

        $key = $dataIndex = $config['key']??'';

        $props = $config['props']??'';
        $this->props = $props;

        if(isset($props['dataIndex']) && $props['dataIndex'])
        {
            $key = $props['dataIndex'];
        }

        if(is_array($key))
        {
            if(count($key) == 1)
            {
                $dataIndex = $key[0];
            }
            $key = $key[0];
        }

        if(in_array($key,['id','parent_id','created_at_s','displayorder']))
        {
            $this->data = $key;
            return;
        }
        $columns = json_decode($model['columns'],true);
        $columns[] = [
            'name'=>'created_at',
            'title'=>'创建时间',
            'form_type'=>'datetime'
        ];
        $columns[] = [
            'name'=>'updated_at',
            'title'=>'更新时间',
            'form_type'=>'datetime'
        ];
        $schema = Utils::arrGet($columns,'name',$key);
        $this->schema = $schema;

        $relation = Utils::arrGet($model['relations'],$schema?'local_key':'name',Utils::uncamelize($key));
        $this->relation = $relation;
        if(!$schema && !$relation)
        {
            return;
        }

        //如果是关联模型的字段
        if(is_array($dataIndex) && count($dataIndex) > 1)
        {
            $this->data = $this->foreignModel($dataIndex);
            return;
        }
        

        $p_title = $props['title']??'';
        $title = $config['title']??'';
        $title = $p_title?:$title;

        $fieldProps = $props['fieldProps']??'';
        $formItemProps = $props['formItemProps']??'';

        //$title = $config['title']??'';
        $readonly = $config['readonly']??'';
        $hidden = $config['hidden']??'';//是否隐藏
        $set_label = $config['label']??'';
        $required = $config['required']??'';
        $placeholder = $config['placeholder']??'';
        //$extra = $config['name']??'';

        $d = ['dataIndex'=>$dataIndex,'title'=>$title?:($schema?$schema['title']:$relation['title'])];

        if($readonly)
        {
            $d['readonly'] = true;
        }
        $this->readonly = $readonly;

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

        if($relation && $set_label)
        {
            //如果有关联数据 并且设置了读取lable字段名称
            $key = [Utils::uncamelize($relation['name'])];
            $key = array_merge($key,explode('.',$set_label));
            $d['dataIndex'] = $key;
        }
        $_formItemProps = [];
        if($required)
        {
            $_formItemProps['rules'] = [
                [
                    "required"=>true,
                ]
                ];
        }
        if($hidden)
        {
            $_formItemProps['hidden'] = true;
        }
        if(!empty($_formItemProps))
        {
            $d['formItemProps'] = $_formItemProps;
        }
        
        $this->data = $d;

        if($form_type && method_exists(self::class,$form_type))
        {
            $this->$form_type();
        }

        if($placeholder)
        {
            if(isset($this->data['fieldProps']))
            {
                $this->data['fieldProps']['placeholder'] = $placeholder;
            }else
            {
                $this->data['fieldProps'] = ['placeholder'=>$placeholder];
            }
        }

        if($fieldProps && !is_string($fieldProps))
        {
            if(isset($this->data['fieldProps']))
            {
                
                $this->data['fieldProps'] = array_merge($fieldProps,$this->data['fieldProps']);
            }else
            {
                $this->data['fieldProps'] = $fieldProps;
            }
        }

        if($formItemProps && !is_string($formItemProps))
        {
            if(isset($this->data['formItemProps']))
            {
                
                $this->data['formItemProps'] = array_merge($formItemProps,$this->data['formItemProps']);
            }else
            {
                $this->data['formItemProps'] = $formItemProps;
            }
        }

        if(isset($props['tooltip']))
        {
            $this->data['tooltip'] = $props['tooltip'];
        }

        if(isset($props['outside']))
        {
            $this->data = array_merge($this->data,$props['outside']);
        }

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
            if($action == 'modalTable')
            {
                $fieldProps = [];
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

    public function foreignModel($dataIndex)
    {
        $new_index = $dataIndex;
        array_shift($new_index);
        $config = $this->config;
        $relation = $this->relation;
        $new_config = $config;
        $new_config['key'] = $new_index;
        $new_model = (new Model())->where(['id'=>$relation['foreign_model_id']])->with(['relations.foreignModel.menu'])->first();
        $formItem = new FormItem($new_config,$new_model,$this->menus,$this->models);
        $data = $formItem->data;
        $data['dataIndex'] = $dataIndex;
        return $data;
    }

    public function saFormTable()
    {
        $fieldProps = [];
        $d = $this->data;
        $relation = $this->relation;
        $d['readonly'] = true;
        if($this->readonly)
        {
            $fieldProps['readonly'] = true;
        }
        //如果是saFormTable 表单中的table 需要读取该关联模型所对应的第一个菜单的所形成的地址，这样组件可以在页面中根据这个path获取该页面的配置参数信息
        if($relation && $relation['foreign_model'] && $relation['foreign_model']['menu'])
        {
            $path = array_reverse(Utils::getPath($relation['foreign_model']['menu'],$this->menus,'path'));
            $fieldProps['path'] = implode('/',$path);
            $fieldProps['foreign_key'] = $relation['foreign_key'];
            $fieldProps['local_key'] = $relation['local_key'];
            $fieldProps['name'] = $d['title'];
            unset($d['title']);
        }
        
        if(!empty($fieldProps))
        {
            $d['fieldProps'] = $fieldProps;
        }
        $this->data = $d;
        return;
    }

    /**
     * 弹出层table选择器
     * 多选数据结构必须是1对多
     * 单选 1对1 
     * @return void
     */
    public function modalSelect()
    {
        $d = $this->data;
        $relation = $this->relation;
        if($this->readonly)
        {
            //unset($d['valueType']);
            //$d['dataIndex'] = [$relation['name'],$label];
        }else
        {
            $d['fieldProps'] = [];
            $fieldProps = $this->props['fieldProps']??'';
            $set_url = $fieldProps['url']??'';
            //如果没有自定义url 才自动查找菜单路径
            if($relation && $relation['foreign_model'])
            {
                //需要找到该关联所关联到哪个菜单下面 读取出后台路由地址
                $d['fieldProps']['name'] = $relation['name'];
                if($relation['foreign_model']['menu'] && !$set_url)
                {
                    //如果关联模型 也关联了菜单 直接使用第一个匹配的这个菜单的url地址
                    $path = array_reverse(Utils::getPath($relation['foreign_model']['menu'],$this->menus,'path'));
                    $d['fieldProps']['url'] = implode('/',$path);
                }
                //如果没有绑定菜单，直接在配置页面中手动设置 url 地址
                
            }
        }
        $this->data = $d;
        return;
    }

    public function select()
    {
        $d = $this->data;
        $setting = $this->schema['setting']??[];
        $table_menu = $this->schema['table_menu']??'';
        $d['fieldProps'] = [];
        if($this->relation)
        {
            $d['requestDataName'] = $this->schema['name'].'s';
            $label = $value = '';
            if(isset($setting['label']) && isset($setting['value']))
            {
                $label = $setting['label'];
                $value = $setting['value'];
            }else
            {
                if($this->schema['form_type'] == 'select')
                {
                    $label = 'title';
                    $value = 'id';
                }
                
            }
            if(!$table_menu && $label)
            {
                $d['fieldProps'] = ['fieldNames'=>[
                    'label'=>$label,'value'=>$value
                ]];
            }
            if($this->schema['form_type'] == 'selects')
            {
                $d['fieldProps']['mode'] = 'multiple';
            }
        }else
        {
            if(isset($setting['json']))
            {
                if(is_string($setting['json']))
                {
                    $d['fieldProps']['options'] = json_decode($setting['json'],true);
                }else
                {
                    $d['fieldProps']['options'] = $setting['json'];
                }
                if($this->form_type == 'selects')
                {
                    $d['fieldProps']['mode'] = 'tags';
                }
            }
        }
        if($this->readonly)
        {
            //只读的话 删除valueType 直接显示数据了
            //unset($d['valueType']);
            //$d['dataIndex'] = [$relation['name'],$label];
        }
        $this->data = $d;
    }

    public function debounceSelect()
    {
        $d = $this->data;
        $relation = $this->relation;
        $setting = $this->schema['setting']??[];
        $label = $setting['label']??'';
        $value = $setting['value']??'';
        //输入搜索select

        if($this->readonly)
        {
            //unset($d['valueType']);
            //$d['dataIndex'] = [$relation['name'],$label];
        }else
        {
            $d['fieldProps'] = [];
            if($relation && $relation['foreign_model'])
            {
                //需要找到该关联所关联到哪个菜单下面 读取出后台路由地址
                if($relation['foreign_model']['menu'])
                {
                    $path = array_reverse(Utils::getPath($relation['foreign_model']['menu'],$this->menus,'path'));
                    $d['fieldProps'] = [
                        'fetchOptions'=>implode('/',$path)
                    ];
                }
                
            }
            if($label && $value)
            {
                $d['fieldProps'] = array_merge($d['fieldProps'],['fieldNames'=>['label'=>$label,'value'=>$value]]);
            }
        }
        $this->data = $d;
        return;
    }

    public function uploader()
    {
        $setting = $this->schema['setting']??[];
        if(isset($setting['image_count']))
        {
            $this->data['fieldProps'] = ['max'=>intval($setting['image_count'])];
        }
        return;
    }

    public function switch()
    {

        $setting = $this->schema['setting']??[];
        $open = $setting['open']??'';
        $close = $setting['close']??'';
        //switch开关
        if($open && $close)
        {
            $default = $this->schema['default']??1;
            $this->data['fieldProps'] = [
                "checkedChildren"=>$open,
                "unCheckedChildren"=>$close,
                "defaultChecked"=>$default?true:false
            ];
            
        }
        //$this->data['initialValue'] = true;//switch 默认设置为选中状态
        return;
    }

    public function cascader()
    {
        //多选分类
        $this->data['requestDataName'] = $this->schema['name'].'s';
        $this->data['fieldProps'] = [
            'placeholder'=>'请选择'.$this->schema['title'],
            'showCheckedStrategy'=>'SHOW_CHILD'
        ];
        if($this->schema['form_type'] == 'cascaders')
        {
            $this->data['fieldProps']['multiple'] = true;
        }
        return;
    }

    public function pca()
    {
        //省市区选择
        $setting = $this->schema['setting']??[];
        if(isset($setting['pca_level']))
        {
            $this->data['fieldProps'] = [
                'level'=>intval($setting['pca_level'])
            ];
        }
    }

    public function datetime()
    {
        
    }

    public function permGroup()
    {
        $this->data['fieldProps'] = [
            'requestNames'=>['perms']
        ];
    }

    public function digit()
    {
        $this->data['width'] = 'md';
    }
}