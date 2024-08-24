<?php
namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\dev\design\FormService;
use Echoyl\Sa\Services\dev\design\PanelService;
use Echoyl\Sa\Services\dev\design\TableService;
use Echoyl\Sa\Services\dev\utils\Dump;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\HelperService;
use Illuminate\Support\Arr;

/**
 * @property \Echoyl\Sa\Services\AdminAppService $service
 */
class MenuController extends CrudController
{
    public $model;
    public $cid = 0;
    public function __construct(Menu $model)
    {
        parent::__construct();
        $this->model = $model;
        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            //'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
        ];
        if($post_parent_id)
        {
            $this->default_post['parent_id'] = intval($post_parent_id);
        }
        $ds = new DevService;
        $this->parse_columns = [
            ['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
            ['name' => 'admin_model', 'type' => 'model', 'default' => '','class'=>Model::class],
            ['name' => 'admin_model_id', 'type' => 'select', 'default' => 0,'with'=>true,'data'=>$ds->getModelsTree()],
            ["name" => "type", "type" => "select", "default" => 'system', "data" => [
                ["label" => "项目", "value" => env('APP_NAME')],
                ["label" => "系统", "value" => 'system'],
            ], "with" => true],
            ["name" => "state","type" => "switch","default" => 1,"with" => true,"data" => [
                ["label" => "启用","value" => 1],
                ["label" => "禁用","value" => 0],
            ],"table_menu" => true],
            ['name'=>'desc','type'=>'json','default'=>''],
            ['name'=>'perms','type'=>'json','default'=>''],
            ['name'=>'icon','type'=>'select','default'=>''],
            ['name'=>'status','type'=>'switch','default'=>1,"with" => true,"data" => [
                ["label" => "显示","value" => 1],
                ["label" => "隐藏","value" => 0],
            ]],
            ['name'=>'form_config','type'=>'json','default'=>''],
            ['name'=>'other_config','type'=>'json','default'=>''],
            ['name'=>'table_config','type'=>'json','default'=>''],
            ['name'=>'setting','type'=>'json','default'=>''],
        ];

        $this->can_be_null_columns = ['title','admin_model_id','icon','category_id','other_config'];

        $this->with_column = ['adminModel.relations.foreignModel.menu'];
    }

    public function index()
    {
        //修改获取分类模式 直接递归 查询数据库获取数据
        $search = [];
        $ds = new DevService;
        $this->parseWiths($search);
        //$search['icons'] = (new Menu())->where([['icon','!=','']])->get()->pluck('icon');
        //$search['table_menu'] = [['value'=>env('APP_NAME'),'label'=>'项目菜单'],['value'=>'system','label'=>'系统菜单']];
        $search['table_menu'] = ['state'=>$search['states']];
        $types = ['system',$ds->appname()];
        $table_menu_id = request('state',1);
        $model = $this->model->whereIn('type',$types);
        //d($table_menu_id);
        if($table_menu_id != 'all')
        { 
            $model = $model->where('state',$table_menu_id);
        }

        $data = HelperService::getChildFromData($model->get()->toArray(),function($item){
            $this->parseData($item,'decode','list');
            return $item;
        },[['displayorder','desc'],['id','asc']]);
        //d($data);
        
        $search['menus'] = $ds->getMenusTree();
        return $this->list($data,count($data),$search);

    }

    public function clearCache()
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);
        return $this->success('success');
    }

    public function afterPost($id, $data)
    {
        if($this->action_type == 'add')
        {
            $this->tableConfig($id);
            $this->formConfig($id);
            $this->otherConfig($id);
        }else
        {
            $desc = $data['desc']?json_decode($data['desc'],true):[];
            $this->updateDesc($desc,$data);
            $this->otherConfig($id);
        }
        $this->clearCache();
        return;
    }

    public function postData(&$item)
    {
        if(isset($item['admin_model']) && isset($item['admin_model']['columns']))
        {
            $item['admin_model']['columns'] = array_merge($item['admin_model']['columns'],array_values(collect(Utils::$title_arr)->map(function($v,$k){
                return ['title'=>$v,'name'=>$k];
            })->toArray()));
            $item['allModels'] = DevService::allModels();

        }
        if(!$this->is_post)
        {
            if(isset($item['form_config']))
            {
                
                $formColumns = $item['form_config'];
                $item['tabs'] = [];
                if(isset($formColumns['tabs']))
                {
                    foreach($formColumns['tabs'] as $key=>$tab)
                    {
                        if(isset($tab['title']))
                        {
                            $item['tabs'][] = ['title'=>$tab['title']];
                        }else
                        {
                            $item['tabs'][] = $tab['tab'];
                        }
                        
                        $k = $key?'form_config'.$key:'form_config';
                        $item[$k] = $tab['config'];
                    }
                }else
                {
                    $item['tabs'][] = ['title'=>'基础信息'];
                }
                
            }else
            {
                $item['tabs'][] = ['title'=>'基础信息'];
            }
            //新增全部菜单选择
            $ds = new DevService;
            $item['menus'] = $ds->getMenusTree();//添加 confirm form | modal table 可以直接选择已创建的菜单信息
        }else{
            //带入用户数据以刷新前台页面数据
            $item['currentUser'] = $this->getUserInfo();
        }

    }

    public function beforePost(&$data, $id = 0, $item)
    {
        //新增菜单的初始化数据
        if(!$id)
        {
            $page_type = $data['page_type']??'table';
            $data['page_type'] = $page_type;

            if(!isset($data['admin_model_id']))
            {
                return;
            }

            $table = [
                ['key'=>'option']
            ];
            $form = [
                'tabs'=>[
                    [
                        'tab'=>['title'=>'基础信息'],
                        'config'=>[]
                    ]
                ],
            ];

            switch($page_type)
            {
                case 'table':
                case 'category':
                    $data['table_config'] = $table;
                    $data['form_config'] = $form;
                    break;
                case 'form':
                    $data['form_config'] = $form;
                    break;
                case 'panel':
                    break;
                default:
                
            }
        }

        return;

    }

    /**
     * 复制菜单至
     *
     * @return void
     */
    public function copyTo()
    {
        $id = request('base.id');
        $toid = request('base.toid');
        //当前菜单
        $data = $this->model->where(['id'=>$id])->first();
        $to = $this->model->where(['id'=>$toid])->first();
        if(!$data)
        {
            return $this->fail([1,'数据错误']);
        }
        if($to)
        {
            $parent_id = $to['id'];
            $type = $to['type'];
        }else
        {
            //无目标菜单 复制到最外层
            $parent_id = 0;
            $type = $data['type'];
        }

        $data = $data->toArray();
        unset($data['id']);
        $data['parent_id'] = $parent_id;
        $data['title'] .= ' - 复制';
        $data['type'] = $type;
        $this->model->insert($data);

        return $this->success('操作成功');
    }

    /**
     * 将菜单移动到
     *
     * @return void
     */
    public function moveTo()
    {
        $id = request('base.id');
        $toid = request('base.toid');
        //当前菜单
        $data = $this->model->where(['id'=>$id])->first();

        if(!$data)
        {
            return $this->fail([1,'数据错误']);
        }

        $to = $this->model->where(['id'=>$toid])->first();

        if(!$to)
        {
            //无目标则 移动至最外层
            $parent_id = 0;
        }else
        {
            $parent_id = $to['id'];
        }

        $update = [
            'parent_id'=>$parent_id
        ];

        $this->model->where(['id'=>$data['id']])->update($update);
        $data['parent_id'] = $parent_id;
        //将该菜单下的所有数据对应的url全部重新生成一遍
        $this->clearCache();
        $this->syncMenuUrl($data);

        return $this->success('操作成功');
    }

    public function syncMenuUrl($item)
    {
        //更新当前菜单的url
        //无数据或未指向模型 则返回
        if($item && $item['admin_model_id'])
        {
            $desc = $item['desc']?json_decode($item['desc'],true):[];
            $this->updateDesc($desc,$item);
        }
        //查找子菜单
        $children = $this->model->where(['parent_id'=>$item['id']])->get()->toArray();
        foreach($children as $val)
        {
            $this->syncMenuUrl($val);
        }
        return;
    }

    // public function postData(&$item)
    // {
    //     sleep(5);
    //     return;
    // }

    protected function getItem($name = 'table_config',$id = 0)
    {   
        if(!$id)
        {
            $id = request('base.id');
            $config = request('base.'.$name);
        }else
        {
            $config = false;
        }
        
        $item = $this->model->where(['id'=>$id])->with($this->with_column)->first();
        if(!$item)
        {
            return $this->fail([1,'请先提交数据']);
        }
        $item = $item->toArray();
        if(!$config)
        {
            $config = isset($item[$name]) && $item[$name]?json_decode($item[$name],true):[];
        }

        return ['config'=>$config,'item'=>$item];
    }

    /**
     * 列表配置信息
     *
     * @return string
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
        $tool_bar_button = [];//头部操作栏
        $select_bar_button = [];//底部选择操作栏
        $json = [];

        
        $need_update_config = false;
        $need_update_perms = false;
        $perms = $item['perms']?json_decode($item['perms'],true):[];
        $setting = $item['setting']?json_decode($item['setting'],true):[];

        foreach($config as $kv=>$val)
        {
            $key = Arr::get($val,'key');
            if($item['page_type'] == 'category' && $key == 'id')
            {
                //这里注入一个 如果是分类页面 自动将第一列改成 title + id 显示
                //20231227 checkbox改成了鼠标悬浮后显示 这里不用了 应该需要删除id这个列表字段了 当不能checkable后才显示id
                //$val = json_decode('{"key":"id","props":{"items":[{"id":"02bhysgdg9s","domtype":"text","btn":{"text":"{{record.title+ \' - \' + record.id}}"}}]},"type":"customerColumn"}',true);
            }
            //加入uid设置 如果没有uid 后台自动加入uid
            if(!isset($val['uid']) || !$val['uid'])
            {
                $val['uid'] = HelperService::uuid();
                $need_update_config = true;
                $config[$kv] = $val;
            }
            if($item['admin_model'])
            {
                $columns = $ds->modelColumn2JsonTable($item['admin_model'],$val,$setting);
            }else
            {
                $columns = [];
            }
            
            if(isset($columns['valueType']) && in_array($columns['valueType'],['import','export','toolbar']))
            {
                $tool_bar_button[] = $columns;
                //自动将导入导出加入到权限设置中
                if(in_array($columns['valueType'],['import','export']))
                {
                    $perms[$columns['valueType']] = $columns['valueType'] == 'import'?'导入':'导出';
                    $need_update_perms = true;
                }
            }elseif(isset($columns['valueType']) && in_array($columns['valueType'],['selectbar']))
            {
                $select_bar_button[] = $columns;
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
        $pre_update = [];
        if($need_update_config)
        {
            $pre_update['table_config'] = json_encode($config);
            
        }
        if($need_update_perms)
        {
            $pre_update['perms'] = json_encode($perms);
        }
        if(!empty($pre_update))
        {
            $this->model->where(['id'=>$item['id']])->update($pre_update);
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

        $desc['selectRowBtns'] = $select_bar_button;
        
        $desc['tableColumns'] = $tableColumns;

        return $this->updateDesc($desc,$item,['table_config'=>empty($config)?'':json_encode($config)]);

    }

    protected function formTabConfig($item,$config)
    {
        //根据form配置生成json配置
        $json = [];
        $ds = new DevService;
        $need_update_config = false;
        foreach($config as $kv=>$val)
        {
            if(!isset($val['uid']) || !$val['uid'])
            {
                $val['uid'] = HelperService::uuid();
                $need_update_config = true;
                $config[$kv] = $val;
            }
            $group_title = isset($val['props']) && isset($val['props']['title']) ? $val['props']['title'] : '';
            $columns = [];
            if(isset($val['columns']))
            {
                //读取列
                $keys = $val['columns'];
                foreach($keys as $kk=>$vv)
                {
                    if(!isset($vv['uid']) || !$vv['uid'])
                    {
                        $vv['uid'] = HelperService::uuid();
                        $need_update_config = true;
                        $keys[$kk] = $vv;
                    }
                }
                $config[$kv]['columns'] = $keys;
                // if($item['admin_model'])
                // {
                    
                // }
                $columns = $ds->modelColumn2JsonForm($item['admin_model'],$keys);
                
                // if(empty($columns))
                // {
                //     $columns = false;
                // }
            }
            if($columns !== false)
            {
                $json_item = ['valueType'=>'group','uid'=>$val['uid'],'columns'=>$columns];
                if($group_title)
                {
                    $json_item['title'] = $group_title;
                }
                
                $json[] = $json_item;
            }
            
            
        }
        return [$json,$need_update_config?$config:false];
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
        $desc = json_decode($item['desc'],true);
        //如果有tab

        $input_tabs = request('base.tags');
        $tabs = false;
        //数据库读取配置 + 配置中已经有了tabs设置
        if($id && isset($config['tabs']))
        {
            $tabs = $config['tabs'];
        }else
        {
            if($input_tabs)
            {
                foreach($input_tabs as $key=>$tab)
                {
                    if(isset($tab['hidden']) && $tab['hidden'])
                    {
                        continue;
                    }
                    $tabs[] = [
                        'tab'=>$tab,
                        'config'=>$key?request('base.form_config'.$key):$config
                    ];
                }
            }
        }
        
        if($tabs !== false)
        {
            $_tabs = [];
            foreach($tabs as $key=>$tab)
            {
                //$_config = $key?request('base.form_config'.$key):$config;
                $need_update_config = false;
                [$formColumns,$update] = $this->formTabConfig($item,$tab['config']);
                if(!isset($tab['tab']) && isset($tab['title']))
                {
                    $tab['tab'] = [
                        'title'=>$tab['title']
                    ];
                    unset($tab['title']);
                    $need_update_config = true;
                }
                if(!isset($tab['uid']) || !$tab['uid'])
                {
                    $tab['uid'] = HelperService::uuid();
                    $need_update_config = true;
                }
                if(isset($tab['props']) && isset($tab['props']['outside']))
                {
                    $tab['tab']['props'] = $tab['props']['outside'];
                }
                $_tabs[] = [
                    'tab'=>$tab['tab'],
                    'uid'=>$tab['uid'],
                    'formColumns'=>$formColumns
                ];
                if($update)
                {
                    $tab['config'] = $update;
                    $need_update_config = true;
                }
                if($need_update_config)
                {
                    $tabs[$key] = $tab;
                }
            }
            //d($tabs);
            if(isset($desc['formColumns']))
            {
                unset($desc['formColumns']);
            }
            $desc['tabs'] = $_tabs;
            $config = ['tabs'=>$tabs];

        }else
        {
            if(isset($desc['tabs']))
            {
                unset($desc['tabs']);
            }
            //将默认加入tab（所有form都将是 tab模式 tab => [[row=>[...columns]]]） 去掉 单独 formColumns
            [$formColumns,$update] = $this->formTabConfig($item,$config);
            $base_tab = [
                'tab'=>['title'=>'基础信息'],
                'uid'=>HelperService::uuid()
            ];
            $_tabs = [
                array_merge($base_tab,['formColumns'=>$formColumns])
            ];
            if(isset($desc['formColumns']))
            {
                unset($desc['formColumns']);
            }
            $desc['tabs'] = $_tabs;
            if($update)
            {
                $config = $update;
            }
            $config = ['tabs'=>[array_merge($base_tab,['config'=>$config])]];
        }
        //默认还是会更新一次form_config 在之前已经生成了group的uid 和 columns 的uid
        return $this->updateDesc($desc,$item,['form_config'=>empty($config)?'':json_encode($config)]);
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
        $keep_column = ['formColumns','toolBarButton','selectRowBtns','leftMenu','table_menu_key','tableColumns','tabs'];
        foreach($keep_column as $kc)
        {
            if(isset($desc[$kc]))
            {
                $_desc[$kc] = $desc[$kc];
            }
        }
        $desc = array_merge($_desc,$config);

        return $this->updateDesc($desc,$item,['other_config'=>empty($config)?'':json_encode($config)]);
    }

    protected function updateDesc($desc,$item,$data = [])
    {
        $desc = $this->getDescUrl($item,$desc);

        $data['desc'] = json_encode($desc);
        $this->model->where(['id'=>$item['id']])->update($data);
        return $this->success('操作成功');
    }
    /**
     * 根据menu记录获取desc字段中的url 和 postUrl
     *
     * @param [type] $item
     * @param array $desc
     * @return void
     */
    public function getDescUrl($item,$desc = [])
    {
        $ds = new DevService;
        //所请求使用菜单的path路径
        //如果没有选择模型清除链接
        if($item['admin_model_id'])
        {
            $path = array_reverse($ds->getPath($item,$ds->allMenu(),'path'));
            $desc['url'] = implode('/',$path);
            if($item['page_type'] == 'form')
            {
                //如果指定form页面额外设置 postUrl参数
                $desc['postUrl'] = $desc['url'];
            }
        }else
        {
            $desc['url']  = $desc['postUrl'] = '';
        }
        return $desc;
    }

    /**
     * 列表列字段的排序设置
     *
     * @return void
     */
    public function sortTableColumns()
    {
        $id = request('id');
        $columns = request('columns');

        $ts = new TableService($id);

        if($ts->sort($columns))
        {
            //需要排序后再执行
            $this->formConfig($id);
        }

        $this->tableConfig($id);

        return $this->devEditRet($id);
    }

    /**
     * 编辑列表字段
     *
     * @return void
     */
    public function editTableColumn($type = 'edit')
    {
        $id = request('base.id');

        $base = request('base');

        $edit_type = request('type','base');
        
        $ts = new TableService($id);

        $base = request('base');

        unset($base['id']);

        $action_type = request('base.actionType',$type);

        if(isset($base['actionType']) || $action_type === null)
        {
            unset($base['actionType']);
        }

        $ts->edit($base,$action_type,$edit_type);

        $this->tableConfig($id);

        return $this->devEditRet($id);
    }

    public function deleteTableColumn()
    {
        return $this->editTableColumn('delete');
    }

    public function export($listData = false)
    {
        $c = new Dump;
        [$code,$msg] = $c->export(request('ids'),'menu');
        if($code)
        {
            return $this->fail([1,$msg]);
        }else
        {
            return $this->success($msg);
        }
    }

    public function import()
    {
        $file = request()->file('file');

        $content = file_get_contents($file);

        $dump = new Dump;

        [$code,$msg] = $dump->import($content);

        if($code)
        {
            return $this->fail([1,$msg]);
        }else
        {
            return $this->success(null,[0,$msg]);
        }
    }

    /**
     * form表单字段的排序功能  结构为多个tab 每个tab都是一个 二维数组
     * 排序的话 只支持同一个tab内的改变
     * @return void
     */
    public function sortFormColumns()
    {
        $id = request('id');
        $columns = request('columns');

        $fs = new FormService($id);

        if($fs->sort($columns))
        {
            //需要排序后再执行
            $this->formConfig($id);
        }

        return $this->devEditRet($id,'form');

    }
    public function sortPanelColumns()
    {
        $id = request('id');
        $columns = request('columns');
        
        $ps = new PanelService($id);

        if($ps->sort($columns))
        {
            //需要排序后再执行
            $this->otherConfig($id);
        }

        return $this->devEditRet($id,'panel');

    }

    public function editPanelColumn($type = 'addRow')
    {
        $id = request('base.id');
        
        $ps = new PanelService($id);

        $base = request('base');

        $action_type = request('base.actionType',$type);

        if(isset($base['actionType']) || $action_type === null)
        {
            unset($base['actionType']);
        }
        if($ps->edit($base,$action_type))
        {
            $this->otherConfig($id);
        }

        return $this->devEditRet($id,'panel');
    }

    public function deletePanelColumn()
    {
        return $this->editPanelColumn('delete');
    }

    /**
     * 编辑列表字段
     *
     * @return void
     */
    public function editFormColumn($type = 'edit')
    {
        $id = request('base.id');
        
        $fs = new FormService($id);

        $edit_type = request('type','base');

        $base = request('base');

        unset($base['id']);

        $action_type = request('base.actionType',$type);

        if(isset($base['actionType']) || $action_type === null)
        {
            unset($base['actionType']);
        }

        $fs->edit($base,$action_type,$edit_type);

        $this->formConfig($id);

        return $this->devEditRet($id,'form');
    }

    public function deleteFormColumn()
    {
        return $this->editFormColumn('delete');
    }

    public function getUserInfo()
    {
        $user = AdminService::user();
        $userinfo = AdminService::parseUser($user);
        $userinfo = $this->service->parseUserInfo($userinfo,$user);
        return $userinfo;
    }

    /**
     * 开发模式 编辑菜单后 返回用户数据 刷新前台菜单信息
     *
     * @param [type] $columns
     * @param [type] $data
     * @param [type] $schema
     * @return void
     */
    public function devEditRet($id,$type = 'table')
    {
        $item = $this->model->where(['id'=>$id])->first();
        $desc = json_decode($item['desc'],true);
        $data = $columns = [];
        if($type == 'table')
        {
            $columns = $desc['tableColumns'];
            $data = ['tabs'=>$desc['tableColumns']];
        }elseif($type == 'form')
        {
            $columns = $desc['tabs'];
            $data = ['tabs'=>$desc['tabs']];
        }elseif($type == 'panel')
        {
            $columns = $desc['panel'];
        }

        $ret = [
            'columns'=>$columns,
            'data'=>$data,
            'schema'=>$item->toArray(),
            'currentUser'=>$this->getUserInfo()
        ];
        //design操作不再提示成功信息
        return $this->success($ret,[0,'']); 
    }

    public function remenu()
    {
        $ids = request('ids');
        $this->updateMenuDesc($ids);
        return $this->success('操作成功');
    }

    public function updateMenuDesc($menu_ids = [])
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);
        foreach($menu_ids as $menu_id)
        {
            $this->tableConfig($menu_id);
            $this->formConfig($menu_id);
            $this->otherConfig($menu_id);
        }
        return;
    }

    /**
     * 删除菜单
     *
     * @return void
     */
    public function destroy()
    {
        $id = request('id', 0);
        if ($id) {
            if (!is_array($id)) {
                $id = [$id];
            }
        }
        if (!empty($id)) {
            $m = $this->model->whereIn('id', $id);

            $items = $m->get();
            foreach ($items as $val) {
                $val->delete();
            }
            return $this->success(['currentUser'=>$this->getUserInfo()]);
        }
        return $this->fail([1,'参数错误']);
    }
}
