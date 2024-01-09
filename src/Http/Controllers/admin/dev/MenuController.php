<?php
namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Services\AdminService;
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
                ["label" => "启用","value" => 1],
                ["label" => "禁用","value" => 0],
            ],"table_menu" => true],
            ['name'=>'desc','type'=>'json','default'=>'{}'],
            ['name'=>'perms','type'=>'json','default'=>'{}'],
            ['name'=>'icon','type'=>'select','default'=>''],
            ['name'=>'status','type'=>'switch','default'=>1],
            ['name'=>'form_config','type'=>'json','default'=>'{}'],
            ['name'=>'other_config','type'=>'json','default'=>'{}'],
            ['name'=>'table_config','type'=>'json','default'=>'{}'],
            ['name'=>'setting','type'=>'json','default'=>''],
        ];

        $this->can_be_null_columns = ['title','admin_model_id'];

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
        if($table_menu_id == 'all')
        {
            $where = [];
        }else
        {
            $where = ['state'=>$table_menu_id];
        }

        $data = $this->model->getChild($this->cid,$types,function($item){
            $this->parseData($item,'decode','list');
            return $item;
        },0,1,[['displayorder','desc'],['id','asc']],$where);
        //d($data);
        
        $search['menus'] = $ds->getMenusTree();
        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];

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
        }
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
            $parent_id = $to['parent_id'];
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

            $columns = $ds->modelColumn2JsonTable($item['admin_model'],$val);
            if(isset($columns['valueType']) && in_array($columns['valueType'],['import','export','toolbar']))
            {
                $tool_bar_button[] = $columns;
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
        if($need_update_config)
        {
            $this->model->where(['id'=>$item['id']])->update(['table_config'=>json_encode($config)]);
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

        return $this->updateDesc($desc,$item,['table_config'=>json_encode($config)]);

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

                $columns = $ds->modelColumn2JsonForm($item['admin_model'],$keys);
                if(empty($columns))
                {
                    $columns = false;
                }
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
        $tabs = [];
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
        
        if(!empty($tabs))
        {
            $_tabs = [];
            foreach($tabs as $key=>$tab)
            {
                //$_config = $key?request('base.form_config'.$key):$config;
                
                [$formColumns,$update] = $this->formTabConfig($item,$tab['config']);
                $_tabs[] = [
                    'tab'=>$tab['tab'],
                    'formColumns'=>$formColumns
                ];
                if($update)
                {
                    $tab['config'] = $update;
                    $tabs[$key] = $tab;
                }
            }
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
            [$formColumns,$update] = $this->formTabConfig($item,$config);
            $desc['formColumns'] = $formColumns;
            if($update)
            {
                $config = $update;
            }
        }
        //默认还是会更新一次form_config 在之前已经生成了group的uid 和 columns 的uid
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
        $keep_column = ['formColumns','toolBarButton','selectRowBtns','leftMenu','table_menu_key','tableColumns','tabs'];
        foreach($keep_column as $kc)
        {
            if(isset($desc[$kc]))
            {
                $_desc[$kc] = $desc[$kc];
            }
        }
        $desc = array_merge($_desc,$config);
        //d($desc);
        return $this->updateDesc($desc,$item,['other_config'=>json_encode($config)]);
    }

    protected function updateDesc($desc,$item,$data = [])
    {
        $ds = new DevService;
        //所请求使用菜单的path路径
        $path = array_reverse($ds->getPath($item,$ds->allMenu(),'path'));
        $desc['url'] = implode('/',$path);
        if($item['page_type'] == 'form')
        {
            //如果指定form页面额外设置 postUrl参数
            $desc['postUrl'] = $desc['url'];
        }
        $data['desc'] = json_encode($desc);
        $this->model->where(['id'=>$item['id']])->update($data);
        return $this->success('操作成功');
    }

    /**
     * 列表列字段的排序设置
     *
     * @return void
     */
    public function sortTableColumns()
    {
        $id = request('id');
        $item_data = $this->getItem('table_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }
        $config = $item_data['config'];
        $config_uids = [];

        foreach($config as $val)
        {
            $config_uids[$val['uid']] = $val;
        }

        $columns = request('columns');
        //按照列表排序
        $new_config = [];
        foreach($columns as $val)
        {
            $uid = $val['uid'];
            if(!isset($config_uids[$uid]))
            {
                continue;
            }
            $new_config[] = $config_uids[$uid];
        }
        $this->model->where(['id'=>$id])->update(['table_config'=>json_encode($new_config)]);

        $this->tableConfig($id);

        $item = $this->model->where(['id'=>$id])->first();
        $desc = json_decode($item['desc'],true);
        return $this->devEditRet($desc['tableColumns'],['tabs'=>$desc['tableColumns']],$item->toArray());
    }

    /**
     * 编辑列表字段
     *
     * @return void
     */
    public function editTableColumn($type = 'edit')
    {
        $id = request('base.id');

        $uid = request('base.uid');

        $afterUid = request('base.afterUid');

        $base = request('base');

        $edit_type = request('type','basic');

        unset($base['id']);
        if($afterUid)
        {
            unset($base['afterUid']);
        }

        $item_data = $this->getItem('table_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }
        $config = $item_data['config'];

        $find = false;

        foreach($config as $key=>$val)
        {
            if($uid)
            {
                //编辑
                if($val['uid'] == $uid)
                {
                    if($type == 'delete')
                    {
                        //删除
                        unset($config[$key]);
                    }else
                    {
                        //编辑
                        if($edit_type == 'basic')
                        {
                            if(isset($base['props']) && isset($val['props']))
                            {
                                //基本信息中修改了 props属性
                                $base['props'] = array_merge($val['props'],$base['props']);
                            }
                            $config[$key] = $base;
                        }else
                        {
                            $config[$key] = $base;
                        }
                    }
                    $find = true;
                    break;
                }
            }else{
                //插入
                if($val['uid'] == $afterUid)
                {
                    array_splice($config,$key + 1,0,[$base]);
                    $find = true;
                    break;
                }
            }
            
        }

        if(!$find)
        {
            return $this->fail([1,'字段信息错误']);
        }

        $this->model->where(['id'=>$id])->update(['table_config'=>json_encode(array_values($config))]);

        $this->tableConfig($id);

        $item = $this->model->where(['id'=>$id])->first();
        $desc = json_decode($item['desc'],true);
        return $this->devEditRet($desc['tableColumns'],['tabs'=>$desc['tableColumns']],$item->toArray());
    }

    public function deleteTableColumn()
    {
        return $this->editTableColumn('delete');
    }

    public function export()
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
     * 获取tabs中字段的索引及数据信息
     *
     * @param [type] $tabs
     * @param [type] $uid
     * @return void
     */
    public function getFormColumnIndex($tabs,$uid)
    {
        $index = $index_data = false;
        foreach($tabs as $tk => $tab)
        {
            foreach($tab['config'] as $gk => $group)
            {
                if($group['uid'] == $uid)
                {
                    $index = [$tk,$gk];
                    $index_data = $group;
                }elseif(isset($group['columns']))
                {
                    foreach($group['columns'] as $ck => $cloumn)
                    {
                        if($cloumn['uid'] == $uid)
                        {
                            $index = [$tk,$gk,$ck];
                            $index_data = $cloumn;
                        }
                    }
                }
                
            }
        }
        return [$index,$index_data];
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
        $item_data = $this->getItem('form_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }
        $config = $item_data['config'];
        $tabs = $config['tabs'];
        //查找 active 和 over的字段uid 
        // 1.group -> group 组和组的顺序改变
        // 2.column -> column 列和列的顺序改变
        // 2存在 同group 和 不同group
        // 3.cloumn -> group 列从group拖动出来后 独立成为一个group
        [$active_key,$over_key] = $columns;
        $active = $over = $active_data = false;
        
        [$active,$active_data] = $this->getFormColumnIndex($tabs,$active_key);
        [$over] = $this->getFormColumnIndex($tabs,$over_key);

        $tab = $tabs[$active[0]];
        $active_count = count($active);
        $over_count = count($over);
        $drag_type = '';

        if($active_count == 2 && $over_count == 2)
        {
            //组和组
            $drag_type = 'g2g';
            $tab['config'] = HelperService::arrayMove($tab['config'],$active[1],$over[1]);
        }

        if($active_count == 3 && $over_count == 3)
        {
            $drag_type = 'c2c';
            if($active[1] == $over[1])
            {
                //同组交换元素
                $tab['config'][$active[1]]['columns'] = HelperService::arrayMove($tab['config'][$active[1]]['columns'],$active[2],$over[2]);
            }else
            {
                //不同组
                $over_group = $tab['config'][$over[1]]['columns'];
                $active_group = $tab['config'][$active[1]]['columns'];
                //d($over_group,$active_group,$over,$active);
                //插入数组
                array_splice($over_group,$over[2] + 1,0,[$active_data]);
                //将之前的数据删除
                unset($active_group[$active[2]]);
                //d($active_group);
                if(empty($active_group))
                {
                    //之前的组空了 删除组
                    unset($tab['config'][$active[1]]);
                }else
                {
                    $tab['config'][$active[1]]['columns'] = array_values($active_group);
                }
                $tab['config'][$over[1]]['columns'] = array_values($over_group);
            }
            
        }

        if($active_count == 3 && $over_count == 2)
        {
            $drag_type = 'c2g';
            $groups = $tab['config'];
            $active_group = $groups[$active[1]]['columns'];
            //插入新的分组
            array_splice($groups,$over[1] + 1,0,[[
                'uid'=>HelperService::uuid(),
                'columns'=>[$active_data]
            ]]);
            //将之前的数据删除 插入新的分组后 如果是往前拖动 active的分组index需要重新定位
            unset($active_group[$active[2]]);
            if($active[1] > $over[1])
            {
                $active[1]++;
            }
            if(empty($active_group))
            {
                //之前的组空了 删除组
                unset($groups[$active[1]]);
            }else
            {
                $groups[$active[1]]['columns'] = array_values($active_group);
            }
            $tab['config'] = array_values($groups);
        }
        if(!$drag_type)
        {
            return $this->success();
        }
        $tab['config'] = array_values($tab['config']);

        $tabs[$active[0]] = $tab;
        $config['tabs'] = $tabs;

        $this->model->where(['id'=>$id])->update(['form_config'=>json_encode($config)]);

        $this->formConfig($id);

        $item = $this->model->where(['id'=>$id])->first();
        $desc = json_decode($item['desc'],true);
        return $this->devEditRet($desc['tabs'],['tabs'=>$desc['tabs']],$item->toArray());
    }

    /**
     * 编辑列表字段
     *
     * @return void
     */
    public function editFormColumn($type = 'edit')
    {
        $id = request('base.id');

        $uid = request('base.uid');

        $afterUid = request('base.afterUid');

        $base = request('base');

        unset($base['id']);
        if($afterUid)
        {
            unset($base['afterUid']);
        }

        $item_data = $this->getItem('form_config',$id);

        if(!is_array($item_data))
        {
            return $item_data;
        }
        $config = $item_data['config'];

        $tabs = $config['tabs'];

        [$active,$active_data] = $this->getFormColumnIndex($tabs,$uid?:$afterUid);
        $count = count($active);
        // $find = false;
        // $tabs[0]['config'] = array_values($tabs[0]['config']);
        // $config['tabs'] = $tabs;
        //d($active,$active_data);

        if($uid)
        {
            if(!$active)
            {
                return $this->fail([1,'数据错误，刷新后重试']);
            }
            
            //编辑
            if($type == 'delete')
            {
                //删除
                if($count == 2)
                {
                    unset($tabs[$active[0]]['config'][$active[1]]);
                }elseif($count == 3)
                {
                    unset($tabs[$active[0]]['config'][$active[1]]['columns'][$active[2]]);
                    $tabs[$active[0]]['config'][$active[1]]['columns'] = array_values($tabs[$active[0]]['config'][$active[1]]['columns']);
                }
                
            }else
            {
                //编辑
                if($count == 2)
                {
                    //编辑分组
                    $tabs[$active[0]]['config'][$active[1]] = array_merge($tabs[$active[0]]['config'][$active[1]],$base);
                }elseif($count == 3)
                {
                    $old_val = $tabs[$active[0]]['config'][$active[1]]['columns'][$active[2]];
                    if(isset($base['props']) && isset($old_val['props']))
                    {
                        //基本信息中修改了 props属性
                        $base['props'] = array_merge($old_val['props'],$base['props']);
                    }
                    $tabs[$active[0]]['config'][$active[1]]['columns'][$active[2]] = $base;
                }
            }
            $tabs[$active[0]]['config'] = array_values($tabs[$active[0]]['config']);
            $config['tabs'] = $tabs;
        }else{
            //插入
            //d($active);
            if($count == 2)
            {
                //1.插入分组 2.插入列
                //1.有key的话是插入列，没有的话就是插入分组
                if(isset($base['key']) || (isset($base['props']) && isset($base['props']['dataIndex'])))
                {
                    $tab_config = $tabs[$active[0]]['config'][$active[1]];
                    if(isset($tab_config['columns']))
                    {
                        $columns = $tab_config['columns'];
                        array_splice($columns,0,0,[$base]);
                        $tab_config['columns'] = array_values($columns);
                    }else
                    {
                        $tab_config['columns'] = [$base];

                    }
                    $tabs[$active[0]]['config'][$active[1]] = $tab_config;
                }else
                {
                    $tab_config = $tabs[$active[0]]['config'];
                    array_splice($tab_config,$active[1] + 1,0,[$base]);
                    $tabs[$active[0]]['config'] = array_values($tab_config);
                }
                
            }else
            {
                //列后插入 即插入列
                $columns = $tabs[$active[0]]['config'][$active[1]]['columns'];
                array_splice($columns,$active[2] + 1,0,[$base]);
                //d($columns);
                $tabs[$active[0]]['config'][$active[1]]['columns'] = array_values($columns);
            }
            $config['tabs'] = $tabs;
        }


        $this->model->where(['id'=>$id])->update(['form_config'=>json_encode($config)]);

        $this->formConfig($id);

        $item = $this->model->where(['id'=>$id])->first();
        $desc = json_decode($item['desc'],true);

        return $this->devEditRet($desc['tabs'],['tabs'=>$desc['tabs']],$item->toArray());
    }

    public function deleteFormColumn()
    {
        return $this->editFormColumn('delete');
    }

    public function devEditRet($columns,$data,$schema)
    {
        $user = AdminService::user();
        $userinfo = AdminService::parseUser($user);
    
        $userinfo = $this->service->parseUserInfo($userinfo,$user);
        return $this->success(['columns'=>$columns,'data'=>$data,'schema'=>$schema,'currentUser'=>$userinfo]); 
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
}
