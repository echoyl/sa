<?php
namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;

class MenuController extends CrudController
{
    public $model;
    public $cid = 0;
    public function __construct(Menu $model)
    {
        $this->model = $model;
        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
        ];
        $ds = new DevService;
        $this->parse_columns = [
            ['name' => 'parent_id', 'type' => '', 'default' => $this->cid],
            ['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
            ['name' => 'admin_model_id', 'type' => 'select', 'default' => 0,'with'=>true,'data'=>$ds->getModelsTree()],
            ["name" => "type", "type" => "select", "default" => 'system', "data" => [
                ["label" => "项目", "value" => env('APP_NAME')],
                ["label" => "系统", "value" => 'system'],
            ], "with" => true],
            ["name" => "state","type" => "switch","default" => 1,"with" => true,"data" => [
                ["label" => "禁用","value" => 0],
                ["label" => "启用","value" => 1],
            ],"table_menu" => true],
            ['name'=>'desc','type'=>'json','default'=>''],
            ['name'=>'perms','type'=>'json','default'=>''],
            ['name'=>'icon','type'=>'select','default'=>''],
            ['name'=>'status','type'=>'switch','default'=>1],
            ['name'=>'form_config','type'=>'json','default'=>''],
            ['name'=>'other_config','type'=>'json','default'=>''],
            ['name'=>'table_config','type'=>'json','default'=>''],
        ];

        $this->can_be_null_columns = ['title'];

        $this->with_column = ['adminModel.relations.foreignModel.menu'];
    }

    public function index()
    {
        //修改获取分类模式 直接递归 查询数据库获取数据
        $search = [];
        $this->parseWiths($search);
        //$search['icons'] = (new Menu())->where([['icon','!=','']])->get()->pluck('icon');
        $search['table_menu'] = [['value'=>env('APP_NAME'),'label'=>'项目菜单'],['value'=>'system','label'=>'系统菜单']];

        $table_menu_id = request('table_menu_id','all');
        if($table_menu_id == 'all')
        {
            $types = ['system',env('APP_NAME'),''];
        }else
        {
            $types = [$table_menu_id,''];
        }

        $data = $this->model->getChild($this->cid,$types,function($item){
            $this->parseData($item,'decode','list');
            return $item;
        });
        //d($data);

        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];

    }

    // public function postData(&$item)
    // {
    //     sleep(5);
    //     return;
    // }

    public function beforePost(&$data,$id,$item)
    {
        if(isset($data['form_config']))
        {
            //根据form配置生成json配置
            $config = $data['form_config'];
            $json = [];
            $ds = new DevService;
            foreach($config as $val)
            {
                $keys = $val['columns'];
                $columns = $ds->modelColumn2JsonForm($item['admin_model'],$keys);
                if(count($keys) > 1)
                {
                    //form group
                    $json[] = ['valueType'=>'group','columns'=>$columns];
                }else
                {
                    //单个form item
                    $json[] = array_shift($columns);
                }
            }
            $formColumns = $json;
        }
        
        if(isset($data['table_config']))
        {
            //根据form配置生成json配置
            $left_menu =false;
            $tool_bar_button = [];
            $config = $data['table_config'];
            $json = [];
            $ds = new DevService;
            foreach($config as $val)
            {
                $columns = $ds->modelColumn2JsonTable($item['admin_model'],$val);
                if(isset($columns['valueType']) && in_array($columns['valueType'],['import','export','toolbar']))
                {
                    $tool_bar_button[] = $columns;
                }else
                {
                    $json[] = $columns;
                }
                
                if(isset($val['table_menu']) && !empty($val['table_menu']))
                {
                    //如果该字段设置了 table_menu
                    $key = $val['key'];
                    if(is_array($key))
                    {
                        $key = $key[0];
                    }
                    $table_menu_key = $key;
                }

                if(isset($val['left_menu']) && !empty($val['left_menu']))
                {
                    //设置左侧菜单 配置
                    $key = $val['key'];
                    if(is_array($key))
                    {
                        $key = $key[0];
                    }
                    
                    $left_menu = [
                        'name'=>$key.'s',//读取值的复数
                        'url_name'=>$key,
                        'title'=>$columns['title'],
                    ];
                    if(isset($val['left_menu_field']) && $val['left_menu_field'])
                    {
                        [$label,$value] = explode(',',$val['left_menu_field']);
                        $left_menu['field'] = ['title'=>$label,'key'=>$value];
                    }
                }

            }
            
            $tableColumns = $json;
        }
        if(isset($data['other_config']) && $data['other_config'])
        {
            $other_config = $data['other_config'];
        }else
        {
            if($id)
            {
                $other_config = $item['other_config'];
            }
            
        }
        if(isset($tableColumns) || isset($formColumns))
        {
            $desc = json_decode($item['desc'],true);

            $data['desc'] = [
                'tableColumns'=>$desc['tableColumns']??[],
                'formColumns'=>$desc['formColumns']??[],
                'toolBarButton'=>$desc['toolBarButton']??[],
            ];
            if(isset($desc['leftMenu']))
            {
                $data['desc']['leftMenu'] = $desc['leftMenu'];
            }

            if(isset($table_menu_key))
            {
                $data['desc']['table_menu_key'] = $table_menu_key;
            }
            if(isset($left_menu))
            {
                $data['desc']['leftMenu'] = $left_menu;
            }

            if(isset($tool_bar_button))
            {
                $data['desc']['toolBarButton'] = $tool_bar_button;
            }
            
            if(isset($tableColumns))
            {
                $data['desc']['tableColumns'] = $tableColumns;
            }
            if(isset($formColumns))
            {
                $data['desc']['formColumns'] = $formColumns;
            }
            // if($item['admin_model'])
            // {
            //     $path = array_reverse($ds->getPath($item['admin_model'],$ds->allModel()));
            //     $data['desc']['url'] = implode('/',$path);
            // }

            //所有请求使用菜单的path路径
            $path = array_reverse($ds->getPath($item,$ds->allMenu(),'path'));
            $data['desc']['url'] = implode('/',$path);

            if(isset($other_config))
            {
                $data['desc'] = array_merge($data['desc'],$other_config);
            }
            $data['desc'] = json_encode($data['desc']);
        }
    
        
    }
}
