<?php
namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Services\dev\utils\Utils;

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
            ['name' => 'admin_model', 'type' => 'model', 'default' => '','class'=>Model::class],
            ['name' => 'admin_model_id', 'type' => 'select', 'default' => 0,'with'=>true,'data'=>$ds->getModelsTree()],
            ["name" => "type", "type" => "select", "default" => 'system', "data" => [
                ["label" => "项目", "value" => env('APP_NAME')],
                ["label" => "系统", "value" => 'system'],
            ], "with" => true],
            ["name" => "state","type" => "switch","default" => 1,"with" => true,"data" => [
                ["label" => "禁用","value" => 0],
                ["label" => "启用","value" => 1],
            ],"table_menu" => true],
            ['name'=>'desc','type'=>'json','default'=>'{}'],
            ['name'=>'perms','type'=>'json','default'=>'{}'],
            ['name'=>'icon','type'=>'select','default'=>''],
            ['name'=>'status','type'=>'switch','default'=>1],
            ['name'=>'form_config','type'=>'json','default'=>'{}'],
            ['name'=>'other_config','type'=>'json','default'=>'{}'],
            ['name'=>'table_config','type'=>'json','default'=>'{}'],
        ];

        $this->can_be_null_columns = ['title','admin_model_id'];

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
        $ds = new DevService;
        $search['menus'] = $ds->getMenusTree();
        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];

    }

    public function postData(&$item)
    {
        if(isset($item['admin_model']) && isset($item['admin_model']['columns']))
        {
            $item['admin_model']['columns'] = array_merge($item['admin_model']['columns'],array_values(collect(Utils::$title_arr)->map(function($v,$k){
                return ['title'=>$v,'name'=>$k];
            })->toArray()));
        }
    }

    public function copyTo()
    {
        $id = request('id');
        $toid = request('toid');
        //当前菜单
        $data = $this->model->where(['id'=>$id])->first();
        $to = $this->model->where(['id'=>$toid])->first();
        if(!$data || !$to)
        {
            return $this->fail([1,'数据错误']);
        }

        $data = $data->toArray();
        unset($data['id']);
        $data['parent_id'] = $to['id'];
        $data['title'] .= '-复制';
        $data['type'] = $to['type'];

        $this->model->insert($data);

        return $this->success('操作成功');


    }

    // public function postData(&$item)
    // {
    //     sleep(5);
    //     return;
    // }

    protected function getItem($name = 'table_config',$id = 0)
    {   
        $id = request('base.id',$id);
        $config = request('base.'.$name);
        $item = $this->model->where(['id'=>$id])->with($this->with_column)->first();
        if(!$item)
        {
            return $this->fail([1,'请先提交数据']);
        }
        $item = $item->toArray();
        if(!$config)
        {
            $config = $item[$name]?json_decode($item[$name],true):[];
        }

        return ['config'=>$config,'item'=>$item];
    }

    /**
     * 列表配置信息
     *
     * @return void
     */
    public function tableConfig($id = 0)
    {
        $item_data = $this->getItem('table_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }

        $item = $item_data['item'];
        $config = $item_data['config'];

        $ds = new DevService;

        //根据form配置生成json配置
        $left_menu = false;
        $tool_bar_button = [];
        $json = [];
        
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

        $desc = json_decode($item['desc'],true);

        //左侧菜单
        $desc['leftMenu'] = $left_menu;

        //table tab切换
        if(isset($table_menu_key))
        {
            $desc['table_menu_key'] = $table_menu_key;
        }

        //工具菜单
        $desc['toolBarButton'] = $tool_bar_button;
        
        $desc['tableColumns'] = $tableColumns;

        return $this->updateDesc($desc,$item,['table_config'=>json_encode($config)]);

    }

    /**
     * 表单配置信息
     *
     * @return void
     */
    public function formConfig($id = 0)
    {
        $item_data = $this->getItem('form_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }

        $item = $item_data['item'];
        $config = $item_data['config'];

        //根据form配置生成json配置
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
        $desc = json_decode($item['desc'],true);
        $desc['formColumns'] = $formColumns;
        
        return $this->updateDesc($desc,$item,['form_config'=>json_encode($config)]);

    }

    /**
     * 额外配置信息
     *
     * @return void
     */
    public function otherConfig($id = 0)
    {
        $item_data = $this->getItem('other_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }

        $item = $item_data['item'];
        $config = $item_data['config'];
        $desc = json_decode($item['desc'],true);

        //这里需要 合并之前的配置 将生成的配置都保留 其它全部删除 然后合并现有的other 配置
        $_desc = [];
        $keep_column = ['formColumns','toolBarButton','leftMenu','table_menu_key','tableColumns'];
        foreach($keep_column as $kc)
        {
            if(isset($desc[$kc]))
            {
                $_desc[$kc] = $desc[$kc];
            }
        }
        $desc = array_merge($_desc,$config);

        return $this->updateDesc($desc,$item,['other_config'=>json_encode($config)]);
    }

    protected function updateDesc($desc,$item,$data = [])
    {
        $ds = new DevService;
        //所请求使用菜单的path路径
        $path = array_reverse($ds->getPath($item,$ds->allMenu(),'path'));
        $desc['url'] = implode('/',$path);
        $data['desc'] = json_encode($desc);
        $this->model->where(['id'=>$item['id']])->update($data);
        return $this->success('操作成功');
    }
}
