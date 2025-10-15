<?php

namespace Echoyl\Sa\Http\Controllers\admin\web;

use Echoyl\Sa\Models\web\Menu;
use Echoyl\Sa\Services\WebsiteService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\dev\Model;
use Echoyl\Sa\Models\Posts;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\HelperService;
use Echoyl\Sa\Traits\Category as TraitsCategory;

class MenuController extends CrudController
{
    use TraitsCategory;
	public $cid = 0;
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

    var $can_be_null_columns = ['tpl','small_title','desc','content_detail','category_id'];

    public function __construct()
    {
        $this->model = new Menu;
        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
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
        $data['admin_model_ids'] = $ds->getModelsTreeData([env('APP_NAME')]);
        //$data['admin_model_folder_ids'] = $ds->getModelsFolderTree([env('APP_NAME')]);
        HelperService::deImagesFromConfig($data);
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
            //不再检测是否有重复的url，如果有重复以最后一条为准
            // $has_where = [
            //     ['parent_id', '=', $data['parent_id'] ?? 0],
            //     ['alias', '=', $alias],
            //     ['type','=',env('APP_NAME')],
            // ];
            // if ($id) {
            //     $has_where[] = ['id', '!=', $id];
            // }
            // $has = $this->model->where($has_where)->first();
            // //这里检测同级导航菜单是否有相同别名的 有的话返回错误
            // if ($has) {
            //     return ['code' => 1, 'msg' => '统计菜单中不能有重复的URL别名'];
            // }
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
        $pagetype = request('pagetype','list');//页面类型 list || detail
        //找到改模型文件夹下面的category 模型
        //$namespace = 'App\Models\jianfuguanjia\anli';

        $prefix = 'App\Models';

        $model_data = (new Model())->where(['id'=>$admin_model_id])->first();

        if(!$model_data)
        {
            return $this->success([]);
        }

        $ds = new DevService;

        $namespace = array_reverse($ds->getPath($model_data->toArray(),$ds->allModel()));

        array_unshift($namespace,$prefix);

        if($pagetype == 'list')
        {
            //换取分类
            array_pop($namespace);
            $namespace[] = 'Category';
        }

        //$namespace[] = 'Category';
        $category_class = implode('\\',$namespace);
        if(!class_exists($category_class))
        {
            return $this->success([]);
        }

        $model = new $category_class;
        $data = [];
        if($pagetype == 'detail')
        {
            //获取单个内容信息
            $page = 1;
            $psize = request('pageSize',10);

            $keyword = request('keyword');
            $data = $model->select(['id','title']);
            if($keyword)
            {
                $data = $data->where([['title','like','%'.$keyword.'%']]);
            }
            $data = $data->offset(($page - 1) * $psize)->limit($psize)->get()->toArray();
        }else
        {
            if(method_exists($model,'getChild'))
            {
                $data = $model->getChild();
            }
        }

        return $this->success($data);
    }
}
