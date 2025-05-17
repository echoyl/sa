<?php
namespace Echoyl\Sa\Services\dev\utils;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\admin\LocaleService;
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
        $model = $model?:['columns'=>'[]','relations'=>[]];
        $this->model = $model;

        $key = $dataIndex = $config['key']??'';
        $uid = $config['uid'];

        $props = $config['props']??'';
        $this->props = $props;

        $has_customer_dataindex = false;
        $unset_dataindex = false;

        if(isset($props['dataIndex']) && $props['dataIndex'])
        {
            $key = $dataIndex = explode('.',$props['dataIndex']);
            $has_customer_dataindex = true;
        }

        if(is_array($key))
        {
            if(count($key) == 1)
            {
                $dataIndex = $key[0];
            }
            $key = $key[0];
        }
        if(in_array($key,['customer_field']) || !$key)
        {
            $has_customer_dataindex = true;
            $unset_dataindex = true;
        }

        if(in_array($key,['id','created_at_s']))
        {
            return;
            $this->data = [
                'dataIndex'=>$key,
                'formItemProps'=>[
                    'hidden'=>true
                ],
                'uid'=>$uid
            ];
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
        $columns[] = [
            'name'=>'displayorder',
            'title'=>'排序',
            'form_type'=>'digit'
        ];
        $schema = Utils::arrGet($columns,'name',$key);
        $this->schema = $schema;

        $relation = Utils::arrGet($model['relations'],$schema?'local_key':'name',Utils::uncamelize($key));
        $this->relation = $relation;
        if(!$schema && !$relation && !$has_customer_dataindex)
        {
            return;
        }

        //如果是关联模型的字段
        if(is_array($dataIndex) && count($dataIndex) > 1)
        {
            $this->data = $this->foreignModel($dataIndex);
            if($this->data)
            {
                return;
            }
        }
        

        $p_title = $props['title']??'';
        $title = $config['title']??'';
        $title = $p_title?:$title;

        $fieldProps = $props['fieldProps']??'';
        $formItemProps = $props['formItemProps']??'';
        $if = $props['if']??'';

        //$title = $config['title']??'';
        $readonly = $config['readonly']??'';
        $hidden = $config['hidden']??'';//是否隐藏
        $disabled = $config['disabled']??'';//是否禁用
        $set_label = $config['label']??'';
        $required = $config['required']??'';
        $placeholder = $fieldProps['placeholder']??'';
        //$extra = $config['name']??'';

        $d = ['dataIndex'=>$dataIndex,'uid'=>$uid,'title'=>$title?:($schema?$schema['title']:($relation?$relation['title']:''))];
        if($unset_dataindex)
        {
            unset($d['dataIndex']);
        }
        if(!$d['title'])
        {
            unset($d['title']);
        }
        // if($dataIndex == 'sn2')
        // {
        //     d($d);
        // }
        if($readonly)
        {
            $d['readonly'] = true;
        }
        $this->readonly = $readonly;

        $form_type = $config['type']??'';
        if($form_type)
        {
            $this->form_type = $form_type;
        }else
        {
            $form_type = $schema['form_type']??'';
            $this->form_type = $form_type;
        }

        if($form_type)
        {
            $d['valueType'] = Utils::$value_type_map[$form_type]??$form_type;
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

        if(method_exists(self::class,$form_type))
        {
            $this->$form_type();
        }else
        {
            $call_form_type = Utils::$value_type_map[$form_type]??'';

            if($call_form_type && method_exists(self::class,$call_form_type))
            {
                $this->$call_form_type();
            }
        }

        if(!isset($this->data['fieldProps']))
        {
            $this->data['fieldProps'] = [];
        }
        if($disabled)
        {
            $this->data['fieldProps']['disabled'] = true;
        }

        //已取消这个设置 兼容之前的 还写在这里
        if(isset($props['tooltip']))
        {
            $this->data['tooltip'] = $props['tooltip'];
        }

        //tip包含3个数据 1.placeholder 2.tooltip 3.extra
        $tip = $props['tip']??[];
        $tip_placeholder = Arr::get($tip,'placeholder');
        $tip_tooltip = Arr::get($tip,'tooltip');
        $tip_extra = Arr::get($tip,'extra');


        if($tip_tooltip)
        {
            $this->data['tooltip'] = $tip_tooltip;
        }

        if($tip_extra)
        {
            if(isset($this->data['formItemProps']))
            {
                $this->data['formItemProps']['extra'] = $tip_extra;
            }else
            {
                $this->data['formItemProps'] = ['extra'=>$tip_extra];
            }
        }


        if($placeholder || $tip_placeholder)
        {
            $this->data['fieldProps']['placeholder'] = $placeholder?:$tip_placeholder;
        }elseif(isset($d['title']))
        {
            //默认给每个表单设置 placeholder

            if(in_array($form_type,['select','cascader','tmapInput','bmapInput','mapInput','switch','debounceSelect','searchSelect','searchSelects']))
            {
                $this->data['fieldProps']['placeholder'] = $this->placeholder().$d['title'];
            }else
            {
                $this->data['fieldProps']['placeholder'] = $this->placeholder('input').$d['title'];
            }
        }

        if($fieldProps && !is_string($fieldProps))
        {
            $this->data['fieldProps'] = array_merge_recursive($this->data['fieldProps'],$fieldProps);//设置内容应该可以覆盖生成内容
        }

        if($if)
        {
            $this->data['fieldProps'] = array_merge(['if'=>$if],$this->data['fieldProps']);
        }
        //是否字段多语言
        if($schema)
        {
            $locale = Arr::get($schema,'setting.locale');
            if($locale)
            {
                $this->data['fieldProps']['localesopen'] = 1;
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

        
        


        if(isset($props['outside']))
        {
            $this->data = array_merge($this->data,$props['outside']);
        }
        if(isset($props['page']))
        {
            $this->data['page'] = $props['page'];
        }

        //栅格数
        $span = Arr::get($props,'span');
        if($span)
        {
            $this->data['colProps'] = ['span'=>$span];
        }

        if(isset($props['width']))
        {
            $this->data['width'] = is_numeric($props['width'])?intval($props['width']):$props['width'];
        }

        //新增dependencyOn 功能
        if(isset($props['dependencyOn']))
        {
            $this->data['dependencyOn'] = $props['dependencyOn'];
        }

        $this->rules();

        return;
    }

    /**
     * 验证规则检测
     *
     * @return void
     */
    public function rules()
    {
        $props = $this->props;
        if(!isset($props['rules']) || !isset($props['rules']['data']) || !$props['rules']['data'])
        {
            return;
        }
        if(!isset($this->data['formItemProps']))
        {
            $this->data['formItemProps'] = [];
        }

        if(!isset($this->data['formItemProps']['rules']))
        {
            $this->data['formItemProps']['rules'] = $props['rules']['data'];
        }else
        {
            $this->data['formItemProps']['rules'] = array_merge($this->data['formItemProps']['rules'],$props['rules']['data']);
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
        $direction = $props['dom_direction']??'horizontal';//默认元素都是水平排列

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
            if($action == 'confirmForm')
            {
                //通过菜单id 读取菜单的path
                $modal = Arr::get($item,'modal',[]);
                $menu_id = Arr::get($modal,'page',0);
                if($menu_id)
                {
                    $menu = (new Menu())->where(['id'=>$menu_id])->first();
                    if($menu)
                    {
                        $path = array_reverse(Utils::getPath($menu,$this->menus,'path'));
                        $item['modal']['page'] = implode('/',$path);
                    }
                    
                }
                $items[$key] = $item;
            }
        }
        if(isset($this->data['readonly']))
        {
            //不需要再设置 readonly  如果是form的话 没有值 就不会渲染 render函数 所以删掉readonly 可以渲染 renderFormItem 函数
            //由自己控制，如果设置了readonly 对于其它类型的 还是有用的
            //20231030 如果使用了form的record 那么不能使用readonly 不然会组件不能读取record的值
            //unset($this->data['readonly']);
        }
        $this->data['fieldProps'] = ['items'=>$items,'direction'=>$direction];
        return;
    }

    /**
     * 如果字段是关联模型中某个字段的话 去到该关联模型中生成该字段的配置信息
     *
     * @param [type] $dataIndex
     * @return void
     */
    public function foreignModel($dataIndex)
    {
        $new_index = $dataIndex;
        $first = array_shift($new_index);
        $config = $this->config;
        $relation = $this->relation;
        if(!$relation)
        {
            return false;
        }
        $new_config = $config;
        $new_config['key'] = $new_index;
        $new_model = (new Model())->where(['id'=>$relation['foreign_model_id']])->with(['relations.foreignModel.menu'])->first();
        $formItem = new FormItem($new_config,$new_model,$this->menus,$this->models);
        $data = $formItem->data;
        
        $data['dataIndex'] = $dataIndex;

        if(isset($data['requestDataName']))
        {
            if(is_array($data['requestDataName']))
            {
                array_unshift($data['requestDataName'],$first);
            }else
            {
                $data['requestDataName'] = [$first,$data['requestDataName']];
            }
        }

        if(isset($data['fieldProps']) && isset($data['fieldProps']['requestDataName']))
        {
            if(is_array($data['fieldProps']['requestDataName']))
            {
                array_unshift($data['fieldProps']['requestDataName'],$first);
            }else
            {
                $data['fieldProps']['requestDataName'] = [$first,$data['fieldProps']['requestDataName']];
            }
        }
       
        return $data;
    }

    public function saFormTable()
    {
        $fieldProps = [];
        $d = $this->data;
        $relation = $this->relation;
        //$d['readonly'] = true;
        if($this->readonly)
        {
            $fieldProps['readonly'] = true;
        }

        $page = Arr::get($this->props,'page');
        $page_menu = false;
        if($page)
        {
            //选择了page不再检测关系类型
            //因为模型存在多个菜单的关系 使用关系数据的话 可能找不到对应的菜单
            $page_menu = Utils::arrGet($this->menus,'id',$page);
        }else
        {
            //如果是saFormTable 表单中的table 需要读取该关联模型所对应的第一个菜单的所形成的地址，这样组件可以在页面中根据这个path获取该页面的配置参数信息
            if($relation && $relation['foreign_model'] && $relation['foreign_model']['menu'])
            {
                $page_menu = $relation['foreign_model']['menu'];
            }
        }

        if($page_menu)
        {
            $path = array_reverse(Utils::getPath($page_menu,$this->menus,'path'));
            $fieldProps['path'] = implode('/',$path);
            if($relation)
            {
                //当无relation是会报错
                $fieldProps['foreign_key'] = $relation['foreign_key'];
                $fieldProps['local_key'] = $relation['local_key'];
            }
            //表格的title 使用菜单名称
            $fieldProps['name'] = $page_menu['title'];
            //unset($d['title']);
        }

        
        
        if(!empty($fieldProps))
        {
            $d['fieldProps'] = $fieldProps;
        }
        if(isset($d['readonly']))
        {
            unset($d['readonly']);
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
            $fieldProps = $this->props['fieldProps']??[];
            $page = [];
            $props_page = Arr::get($this->props,'page');//选择关联菜单后不在去检测关联
            if($props_page)
            {
                $page['id'] = $props_page;//只指定id，不会覆盖自定义配置columns等
            }else
            {
                $set_url = $fieldProps['url']??'';
                //如果没有自定义url 才自动查找菜单路径
                if($relation && $relation['foreign_model'])
                {
                    //需要找到该关联所关联到哪个菜单下面 读取出后台路由地址
                    $d['fieldProps']['relationname'] = $relation['name'];
                    
                    if($relation['foreign_model']['menu'] && !$set_url)
                    {
                        //如果关联模型 也关联了菜单 直接使用第一个匹配的这个菜单的url地址
                        $path = array_reverse(Utils::getPath($relation['foreign_model']['menu'],$this->menus,'path'));
                        $page['path'] = implode('/',$path);
                    }
                    
                    //如果没有绑定菜单，直接在配置页面中手动设置 url 地址
                }
            }
            if(!empty($page))
            {
                $d['fieldProps']['page'] = $page;
            }
        }
        $this->data = $d;
        return;
    }

    public function select()
    {
        $d = $this->data;
        $setting = $this->schema['setting']??[];
        $json = Arr::get($setting,'json',[]);
        $d['fieldProps'] = [];

        $label = $setting['label']??'';
        $value = $setting['value']??'';
        
        if(in_array($this->form_type,['select','selects','radioButton','checkbox']))
        {
            if($this->schema)
            {
                //当有数据库字段时，自动获取字段名 否则还是使用组件自带的label 和 value
                $label = $label?:'title';
                $value = $value?:'id';
            }
            
        }

        if($label || $value)
        {
            $d['fieldProps']['fieldNames'] = [
                'label'=>$label,'value'=>$value
            ];
        }

        if(($this->relation || !empty($json)) && $this->schema)
        {
            $d['fieldProps']['requestDataName'] = $this->schema['name'].'s';
        }

        if($this->relation && $this->schema)
        {
            if($this->form_type == 'selects')
            {
                $d['fieldProps']['mode'] = 'multiple';
            }
        }else
        {
            //直接使用requestDataName 更改数据后不用再次编辑表单
            // if(isset($setting['json']))
            // {
            //     if(is_string($setting['json']))
            //     {
            //         $d['fieldProps']['options'] = json_decode($setting['json'],true);
            //     }else
            //     {
            //         $d['fieldProps']['options'] = $setting['json'];
            //     }
            // }
            if($this->form_type == 'selects')
            {
                $d['fieldProps']['mode'] = 'tags';
            }
        }

        if($this->form_type == 'radioButton')
        {
            $d['fieldProps']['buttonStyle'] = 'solid';
        }

        if(in_array($this->form_type,['radioButton','checkbox']))
        {
            $d['valueType'] = $this->form_type;
        }

        if($this->form_type == 'select')
        {
            $d['fieldProps']['showSearch'] = true;
        }
        if($this->readonly)
        {
            //只读的话 删除valueType 直接显示数据了
            //unset($d['valueType']);
            //$d['dataIndex'] = [$relation['name'],$label];
        }
        $this->data = $d;
    }

    public function searchSelect()
    {
        return $this->debounceSelect();
    }
    public function searchSelects()
    {
        return $this->debounceSelect();
    }

    public function debounceSelect()
    {
        $d = $this->data;
        $relation = $this->relation;
        $setting = $this->schema['setting']??[];
        $label = $setting['label']??'';
        $value = $setting['value']??'';
        //输入搜索select

        if($this->readonly && $this->form_type == 'search_select')
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
            if($this->form_type == 'searchSelects')
            {
                //多选还是需要开启多选模式
                $d['fieldProps']['mode'] = 'multiple';
            }
        }
        $this->data = $d;
        return;
    }

    public function uploader()
    {
        $setting = $this->schema['setting']??[];
        $fieldProps = [];
        $image_count = Arr::get($setting,'image_count',0);
        $image_crop = Arr::get($setting,'image_crop',false);
        if($image_count)
        {
            $fieldProps['max'] = intval($setting['image_count']);
        }
        if($image_crop)
        {
            $fieldProps['crop'] = true;
        }
        
        if($this->form_type == 'file')
        {
            $fieldProps['type'] = 'file';
        }
        if(!empty($fieldProps))
        {
            $this->data['fieldProps'] = $fieldProps;
        }
        return;
    }

    public function aliyunVideo()
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
            $default = $this->schema['default']??0;
            $this->data['fieldProps'] = [
                "checkedChildren"=>$open,
                "unCheckedChildren"=>$close,
                "defaultChecked"=>$default?true:false
            ];
            
        }
        //$this->data['initialValue'] = true;//switch 默认设置为选中状态
        return;
    }

    /**
     * 国际化 placeholder
     *
     * @param 'select' | 'input' $type
     * @return void
     */
    private function placeholder($type = 'select')
    {
        
        
        if(LocaleService::enable())
        {
            $arr = [
                'select'=>"{{t('form.pleaseselect')}}",
                'input'=>"{{t('form.pleasetypein')}}",
            ];
        }else
        {
            $arr = [
                'select'=>'请选择',
                'input'=>'请输入',
            ];
        }
        return $arr[$type]??'';
    }

    public function cascader()
    {
        //多选分类
        $this->data['requestDataName'] = $this->schema['name'].'s';
        $this->data['fieldProps'] = [
            'placeholder'=>$this->placeholder().$this->schema['title'],
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
                'level'=>intval($setting['pca_level']),
                'topCode'=>Arr::get($setting,'pca_topCode','')
            ];
        }
    }

    public function datetime()
    {
        
    }

    public function permGroup()
    {
        // $this->data['fieldProps'] = [
        //     'requestNames'=>['perms']
        // ];
    }

    public function digit()
    {
        $this->data['width'] = '100%';
    }

    public function textarea()
    {
        //textarea默认4行
        $this->data['fieldProps'] = [
            'rows'=>4
        ];
    }
}