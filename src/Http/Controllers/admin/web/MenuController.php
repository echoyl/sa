<?php

namespace Echoyl\Sa\Http\Controllers\admin\web;

use Echoyl\Sa\Models\web\Menu;
use Echoyl\Sa\Services\WebsiteService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\Posts;
use Echoyl\Sa\Services\dev\DevService;

class MenuController extends CrudController
{
    //
    public $model;
    public $spec_arr = [
        ['key' => 'key', 'title' => '名称', 'type' => 'input'],
        ['key' => 'value', 'title' => '值', 'type' => 'input'],
    ];
    //public $with_column = ['category', 'content'];
    //public $json_columns = ['specs'];

    public $modules = [
        ['id' => 'link', 'title' => '外链'],
        ['id' => 'page', 'title' => '单页'],
        ['id' => 'post', 'title' => '内容'],
    ];

    public $modulesModel = [
        'post' => [
            Posts::class, Category::class,
        ],
    ];

    public $parse_columns = [
        //['name' => 'state', 'type' => 'state', 'default' => 'disable'],
        ['name' => 'parent_id', 'type' => '', 'default' => '0'],
        ['name' => 'banner', 'type' => 'image', 'default' => ''],
        ['name' => 'specs', 'type' => 'config', 'default' => ''],
        ['name' => 'pics', 'type' => 'image', 'default' => ''],
        ['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
        ['name' => 'relate_menu_id', 'type' => 'cascaders', 'default' => ''],
        ['name' => 'content_id', 'type' => 'search_select', 'default' => '0'],
        [
            'name' => 'state',
            'type' => 'switch',
            'default' => 1,
            'table_menu' => true,
            'with' => true,
            'data' => [
                [
                    'label' => '禁用',
                    'value' => 0,
                ],
                [
                    'label' => '启用',
                    'value' => 1,
                ],
            ],
        ],
    ];

    var $can_be_null_columns = ['tpl','small_title','desc','content_detail'];
    public function __construct(Menu $model)
    {
        $this->model = $model;
        $this->default_post = [
            'parent_id' => request('parent_id', 0),
            'category' => ['id' => 0, 'title' => ''],
            'content' => ['id' => 0, 'title' => ''],
            'id' => 0,
            'content_detail' => '',
            'top' => 1,
            'bottom' => 1,
            'state' => true,
        ];
    }

    public function index()
    {
        //修改获取分类模式 直接递归 查询数据库获取数据
        $search = [];
        $this->parseWiths($search);
        $ws = new WebsiteService;
        $search['modules'] = $ws->modules;
        return $this->list($this->model->getChild(0,['type'=>env('APP_NAME')]), 0, $search);
    }

    public function postData(&$data)
    {
        $ws = new WebsiteService;
        $data['spec_arr'] = $ws->spec_arr;
        $data['modules'] = $ws->modules;
        $ds = new DevService;
        $data['admin_model_ids'] = $ds->getModelsFolderTree([env('APP_NAME')]);
        //$data['admin_model_folder_ids'] = $ds->getModelsFolderTree([env('APP_NAME')]);
        if ($data['id']) {

            $data['content_id'] = $ws->menuContent($data);
        }
        // if(isset($data['specs']))
        // {
        //     $data['specs'] = json_decode($data['specs'],true);
        // }

        return;
    }

    public function beforePost(&$data, $id = 0)
    {

        if (isset($data['alias'])) {
            $alias = $data['alias'];
            //匹配
            if (!preg_match('/^[0-9a-zA-Z]+$/', $alias)) {
                $data['alias'] = '';
            }
            if (in_array($alias, ['js', 'css', 'static'])) {
                $data['alias'] = '';
            }
            $has_where = [
                ['parent_id', '=', $data['parent_id'] ?? 0],
                ['alias', '=', $alias],
                ['type','=',env('APP_NAME')],
            ];
            if ($id) {
                $has_where[] = ['id', '!=', $id];
            }
            $has = $this->model->where($has_where)->first();
            //这里检测同级导航菜单是否有相同别名的 有的话返回错误
            if ($has) {
                return ['code' => 1, 'msg' => '统计菜单中不能有重复的URL别名'];
            }
        }

        //检测如果没填写外链也没有填写别名 返回错误信息
        if (!$id) {
            if (!isset($data['alias']) && !isset($data['link'])) {
                return ['code' => 1, 'msg' => '必须填写别名或者外链'];
            }
        }

        $data['type'] = env('APP_NAME');

        return;
    }

    public function category()
    {
        $admin_model_id = request('admin_model_id');
        //找到改模型文件夹下面的category 模型
        //$namespace = 'App\Models\jianfuguanjia\anli';

        $prefix = 'App\Models';

        $model_data = (new Model())->where(['id'=>$admin_model_id])->first();

        if(!$model_data)
        {
            return $this->fail([1,'信息错误，请先选择关联模型']);
        }

        $ds = new DevService;

        $namespace = array_reverse($ds->getPath($model_data->toArray(),$ds->allModel()));

        array_unshift($namespace,$prefix);

        $namespace[] = 'Category';
        $category_class = implode('\\',$namespace);
        if(!class_exists($category_class))
        {
            return $this->success([]);
            return $this->fail([1,'请先创建 '.$model_data['title'].' 下方的Category模型']);
        }

        $model = new $category_class;

        $data = $model->getChild();
        return $this->success($data);
    }
}
