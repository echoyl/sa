<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\dev\Menu;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\utils\Creator;
use Echoyl\Sa\Services\dev\utils\Dev;
use Echoyl\Sa\Services\dev\utils\Dump;
use Echoyl\Sa\Services\dev\utils\MySQLDump;
use Echoyl\Sa\Services\dev\utils\Utils;
use mysqli;

class ModelController extends CrudController
{
    public $model;
    public $cid = 0;
    public function __construct(Model $model)
    {
        $this->model = $model;
        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
        ];
    }

    public function index()
    {
        //修改获取分类模式 直接递归 查询数据库获取数据
        $search = [];
        $this->parseWiths($search);
        //$search['icons'] = (new Menu())->where([['icon','!=','']])->get()->pluck('icon');

        $table_menu_id = request('admin_type', 'all');
        if ($table_menu_id == 'all') {
            $types = ['system', env('APP_NAME'), ''];
        } else {
            $types = [$table_menu_id, ''];
        }

        $data = $this->model->getChild($this->cid, $types, function ($item) {
            $this->parseData($item, 'decode', 'list');
            return $item;
        });
        $search['table_menu'] = ['admin_type'=>[['value' => env('APP_NAME'), 'label' => '项目'], ['value' => 'system', 'label' => '系统']]];
        //d($data);
        $ds = new DevService;
        $search['models'] = $ds->getModelsFolderTree();
        $search['menus'] = $ds->getMenusTree();//增加快速创建内容模块 选择创建到菜单下
        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];
    }

    public function copyToFolder()
    {
        $id = request('id');
        $toid = request('toid');
        //当前模型
        $data = $this->model->where(['id'=>$id])->with(['relations'])->first();
        $to_folder = (new Model())->where(['id'=>$toid])->first();
        if(!$data || !$to_folder || $to_folder['type'] != 0)
        {
            return $this->fail([1,'数据错误']);
        }

        $data = $data->toArray();
        $relations = $data['relations'];
        unset($data['id'],$data['relations']);
        $data['parent_id'] = $to_folder['id'];
        $data['title'] .= '-复制';
        $data['admin_type'] = $to_folder['admin_type'];

        $new_model_id = (new Model())->insertGetId($data);

        //将该模型下的关联信息也复制进去
        foreach($relations as $val)
        {
            unset($val['id']);
            $val['model_id'] = $new_model_id;
            (new Relation())->insert($val);
        }

        return $this->success('操作成功');


    }

    public function checkHas($model_data)
    {
        $model_has = $this->model->where(['name'=>$model_data['name'],'parent_id'=>$model_data['parent_id'],'type'=>$model_data['type'],'admin_type'=>$model_data['admin_type']])->first();
        if(!$model_has)
        {
            $model_id = $this->model->insertGetId($model_data);
        }else
        {
            //更新
            $this->model->where(['id'=>$model_has['id']])->update($model_data);
            $model_id = $model_has['id'];
        }
        return $model_id;
    }
    public function checkHasMenu($model_data)
    {
        $model = new Menu();
        $model_has = $model->where(['path'=>$model_data['path'],'parent_id'=>$model_data['parent_id'],'type'=>$model_data['type']])->first();
        if(!$model_has)
        {
            $model_id = $model->insertGetId($model_data);
        }else
        {
            //更新
            $model->where(['id'=>$model_has['id']])->update($model_data);
            $model_id = $model_has['id'];
        }
        return $model_id;
    }

    /**
     * 快速创建内容模块
     * 1.posts 2.category 3.posts_category 关联
     *
     * @return void
     */
    public function quickCreate()
    {
        if(env('APP_ENV') != 'local')
        {
            return $this->fail([1,'环境错误！']); 
        }

        $title = request('title');
        $name = request('name');

        $model_to_id = request('model_to_id',0);
        $menu_to_id = request('menu_to_id',0);
        $appname = DevService::appname();
        //先创建模型
        //1.列表
        $model_to = $this->model->where(['id'=>$model_to_id])->first();
        $model_to_id = $model_to?$model_to['id']:0; 

        $category_level = request('category_level',1);
        $category_type = request('category_type','single');
        $type = '';
        $relation_type = 'cascaders';
        if($category_level > 1)
        {
            $type = $category_type == 'single'?'cascader':'cascaders';
            $relation_type = $category_type == 'single'?'cascader':'cascaders';
        }else
        {
            $type = $category_type == 'single'?'select':'selects';
            $relation_type = $category_type == 'single'?'one':'cascaders';
        }

        $model_data = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'normal',
            'columns'=>Creator::postsColumns($type),
            'parent_id'=>$model_to_id
        ];
        
        $model_id = $this->checkHas($model_data);
        //2.分类表
        //2.1 先创建posts父级
        $model_category_f = [
            'title'=>$title,
            'name'=>$name,
            'admin_type'=>$appname,
            'type'=>0,//文件夹
            'leixing'=>'normal',
            'parent_id'=>$model_to_id
        ];
        $model_f_id = $this->checkHas($model_category_f);
        //2.2 category
        $model_category_data = [
            'title'=>'分类',
            'name'=>'category',
            'admin_type'=>$appname,
            'type'=>1,
            'leixing'=>'category',
            'columns'=>Creator::$default_category_columns,
            'parent_id'=>$model_f_id
        ];
        $model_category_id = $this->checkHas($model_category_data);
        $model = $this->model->where(['id'=>$model_id])->first();
        $category_model = $this->model->where(['id'=>$model_category_id])->first();
        //创建数据表
        $ds = new DevService;
        $ds->createModelSchema($model);
        $ds->createModelSchema($category_model);
        //3.关联模型
        $relation = [
            'model_id'=>$model_id,
            'title'=>'分类',
            'name'=>'category',
            'type'=>$relation_type,
            'foreign_model_id'=>$model_category_id,
            'foreign_key'=>'id',
            'local_key'=>'category_id',
            'created_at'=>now(),
            'updated_at'=>now(),
            'is_with'=>1
        ];
        $has_relation = (new Relation())->where(['model_id'=>$model_id,'foreign_model_id'=>$model_category_id])->first();
        if(!$has_relation)
        {
            (new Relation())->insert($relation);
        }else
        {
            (new Relation())->where(['id'=>$has_relation['id']])->update($relation);
        }
        //4.1生成model文件
        
        $ds->createModelFile($model);
        $ds->createModelFile($category_model);
        //4.2及controller文件
        $ds->createControllerFile($model);
        $ds->createControllerFile($category_model);

        //5 创建菜单
        //5.1大菜单
        $big_menu = [
            'title'=>$title,
            'path'=>$name,
            'parent_id'=>$menu_to_id,
            'status'=>1,
            'icon'=>'table',
            'state'=>1,
            'type'=>$appname
        ];
        $big_menu_id = $this->checkHasMenu($big_menu);
        //5.3分类菜单
        $category_menu = array_merge([
            'title'=>'分类',
            'path'=>'category',
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'category',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_category_id,
        ],Creator::menuCategory($category_level));
        //5.2列表
        $menu = array_merge([
            'title'=>'列表',
            'path'=>$name,
            'parent_id'=>$big_menu_id,
            'status'=>1,
            'state'=>1,
            'type'=>$appname,
            'page_type'=>'table',
            'open_type'=>'drawer',
            'admin_model_id'=>$model_id
        ],Creator::$menu_posts);
        $menu_category_id = $this->checkHasMenu($category_menu);
        $menu_id = $this->checkHasMenu($menu);
        
        //5.4生成菜单的配置信息 desc
        //所请求使用菜单的path路径
        $this->updateMenuDesc($menu_id);
        $this->updateMenuDesc($menu_category_id);
        return $this->success('操作成功');
    }

    public function updateMenuDesc($menu_id)
    {
        $ds = new DevService;
        $ds->allMenu(true);
        $ds->allModel(true);
        $mc = new MenuController(new Menu());
        $mc->tableConfig($menu_id);
        $mc->formConfig($menu_id);
        $mc->otherConfig($menu_id);
        return;
    }

    /**
     * 导出开发配置sql文件 直接在服务器中运行更新
     *
     * @return void
     */
    public function export()
    {
        //导出dev_menu表
        $appname = DevService::appname();
        $filename = 'update.sql';
        $file = storage_path('app/public/'.$filename);
        //file_put_contents($file,'');
        $check = request('check');
        $export_table = [
            ['dev_menu',''],
            ['dev_model',''],
            ['dev_model_relation',''],
        ];
        if(!in_array('all',$check))
        {
            $export_table[0][1] = "type = 'system' or type = '{$appname}'";
            $export_table[1][1] = "admin_type = 'system' or admin_type = '{$appname}'";
            $model_ids = (new Model())->whereIn('admin_type',['system',$appname])->pluck('id')->toArray();
            $export_table[2][1] = "model_id in (".implode(',',$model_ids).")";
        }
        $c = new Dump;
        foreach($export_table as $tb)
        {
            $c = $c->exportTable($tb[0],$tb[1],$check);
        }
        $c = $c->dumpToFile($file);

        //return $this->success();
        return $this->success(['url'=>tomedia($filename),'download'=>$filename]);
    }

    /**
     * 数据模型创建数据库
     *
     * @return void
     */
    public function createModelSchema($id = 0)
    {
        if(env('APP_ENV') != 'local')
        {
            return $this->fail([1,'环境错误！']); 
        }
        $id = $id?:request('id');
        $data = $this->model->where(['id' => $id])->first();
        if(!$data)
        {
            return $this->fail([1,'参数错误']); 
        }
        $ds = new DevService;
        $ds->createModelSchema($data->toArray());
        return $this->success('操作成功');
    }


    public function createModelFile()
    {
        if(env('APP_ENV') != 'local')
        {
            return $this->fail([1,'环境错误！']); 
        }
        $id = request('id');
        $data = $this->model->where(['id' => $id])->first();

        $ds = new DevService;

        $ds->createModelFile($data);

        return $this->success('操作成功');
    }


    public function createControllerFile()
    {
        if(env('APP_ENV') != 'local')
        {
            return $this->fail([1,'环境错误！']); 
        }
        $id = request('id');
        $data = $this->model->where(['id' => $id])->first();

        $ds = new DevService;

        $ds->createControllerFile($data);

        return $this->success('操作成功');
    }

    public function beforeDestroy($m)
    {
        $list = $m->get();
        foreach($list as $val)
        {
            //删除关系
            (new Relation())->where(['model_id'=>$val['id']])->delete();
        }
        return $m;
    }
}
