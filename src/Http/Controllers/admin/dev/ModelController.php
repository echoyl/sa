<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\utils\Creator;
use Echoyl\Sa\Services\dev\utils\Dump;

class ModelController extends CrudController
{
    public $model;
    public $cid = 0;
    var $can_be_null_columns = ['search_columns'];
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
        $search['foldermodels'] = $ds->getModelsFolderTree();//模型文件夹
        $search['menus'] = $ds->getMenusTree();//增加快速创建内容模块 选择创建到菜单下
        $search['models'] = $ds->getModelsTree();//可选模型
        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];
    }

    public function copyToFolder()
    {
        $id = request('base.id');
        $toid = request('base.toid');
        //当前模型
        $data = $this->model->where(['id'=>$id])->with(['relations'])->first();
        $to_folder = (new Model())->where(['id'=>$toid])->first();
        if(!$data)
        {
            return $this->fail([1,'数据错误']);
        }
        if($to_folder)
        {
            if($to_folder['type'] != 0)
            {
                return $this->fail([1,'所选文件夹类型错误']);
            }
        }else{
            $to_folder = [
                'id'=>0,
                'admin_type'=>DevService::appname()
            ];
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

    /**
     * 保存完后就直接创建 文件
     *
     * @param [type] $id
     * @param [type] $data
     * @return void
     */
    public function afterPost($id, $data)
    {
        $ds = new DevService;

        $ds->createControllerFile($data);

        $ds->createModelFile($data);

        return;
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

        $type = request('base.type');
        $c = new Creator;
        if($type == 'posts')
        {
            $c->postsContent();
        }elseif($type == 'perm')
        {
            $c->permContent();
        }

        
        return $this->success('操作成功');
    }

    

    /**
     * 导出开发配置sql文件 直接在服务器中运行更新
     *
     * @return void
     */
    public function export()
    {
        //导出dev_menu表
        if(request()->isMethod('post'))
        {
            $appname = DevService::appname();
            $filename = 'update.sql';
            $file = storage_path('app/public/'.$filename);
            //file_put_contents($file,'');
            $check = request('base.check',[]);
            $export_table = [
                ['dev_menu',''],
                ['dev_model',''],
                ['dev_model_relation',''],
            ];
            if(!in_array('all',$check))
            {
                //包含系统数据 + app数据
                if(in_array('app',$check))
                {
                    $export_table[0][1] = "type = '{$appname}'";
                    $export_table[1][1] = "admin_type = '{$appname}'";
                    $model_ids = (new Model())->whereIn('admin_type',[$appname])->pluck('id')->toArray();
                    $export_table[2][1] = "model_id in (".implode(',',$model_ids).")";
                }else
                {
                    $export_table[0][1] = "type = 'system' or type = '{$appname}'";
                    $export_table[1][1] = "admin_type = 'system' or admin_type = '{$appname}'";
                    $model_ids = (new Model())->whereIn('admin_type',['system',$appname])->pluck('id')->toArray();
                    $export_table[2][1] = "model_id in (".implode(',',$model_ids).")";
                }
            }
            $c = new Dump;
            foreach($export_table as $tb)
            {
                $c = $c->exportTable($tb[0],$tb[1],$check);
            }
            $c = $c->dumpToFile($file);
    
            //return $this->success();
            return $this->success(['url'=>tomedia($filename),'download'=>$filename]);
        }else
        {
            return $this->success(['check'=>['replace','app']]);
        }
        
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
        $ds->createControllerFile($data);

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
