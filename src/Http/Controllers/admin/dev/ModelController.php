<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;

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

        $this->parse_columns = [
            //['name' => 'state', 'type' => 'state', 'default' => 'disable'],
            ['name' => 'parent_id', 'type' => '', 'default' => $this->cid],
            ["name" => "admin_type", "type" => "select", "default" => env('APP_NAME'), "data" => [
                ["label" => "项目", "value" => env('APP_NAME')],
                ["label" => "系统", "value" => 'system'],
            ], "with" => true],
            //['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
        ];
    }

    public function index()
    {
        //修改获取分类模式 直接递归 查询数据库获取数据
        $search = [];
        $this->parseWiths($search);
        //$search['icons'] = (new Menu())->where([['icon','!=','']])->get()->pluck('icon');

        $table_menu_id = request('table_menu_id', 'all');
        if ($table_menu_id == 'all') {
            $types = ['system', env('APP_NAME'), ''];
        } else {
            $types = [$table_menu_id, ''];
        }

        $data = $this->model->getChild($this->cid, $types, function ($item) {
            $this->parseData($item, 'decode', 'list');
            return $item;
        });
        $search['table_menu'] = [['value' => env('APP_NAME'), 'label' => '项目菜单'], ['value' => 'system', 'label' => '系统菜单']];
        //d($data);

        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];
    }

    public function afterPost($id)
    {
        // $data = $this->model->where(['id'=>$id])->first();

        // $ds = new DevService;

        // $all = $ds->allData($this->model);

        // if($data['type'] == 0)
        // {
        //     //文件夹类型 创建文件夹
        //     $name = implode('/', array_reverse($ds->getPath($data,$all)));
        //     $ds->createFolder($name,$this->path);
        // }else
        // {
        //     //数据模型 需要创建数据库

        // }

    }



    /**
     * 数据模型创建数据库
     *
     * @return void
     */
    public function createModelSchema()
    {
        if(env('APP_ENV') != 'local')
        {
            return $this->fail([1,'环境错误！']); 
        }
        $id = request('id');
        $data = $this->model->where(['id' => $id])->first();

        $ds = new DevService;

        $all = $ds->allModel();

        $name = implode('_', array_reverse($ds->getPath($data, $all)));

        if ($data['columns']) {
            $columns = json_decode($data['columns'], true);
            $ds->createModelSchema($name, $columns);
        }



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

        $all = $ds->allModel();

        $names = array_reverse($ds->getPath($data, $all));

        // if($data['columns'])
        // {
        //     $columns = json_decode($data['columns'],true);
        //     $ds->createModelFile($names);
        // }

        $ds->createModelFile($names, $data);

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

        $all = $ds->allModel();

        $names = array_reverse($ds->getPath($data, $all));

        // if($data['columns'])
        // {
        //     $columns = json_decode($data['columns'],true);
        //     $ds->createModelFile($names);
        // }

        $ds->createControllerFile($names, $data);

        return $this->success('操作成功');
    }
}