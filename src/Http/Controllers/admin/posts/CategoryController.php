<?php

namespace Echoyl\Sa\Http\Controllers\admin\posts;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Services\dev\MenuService;

class CategoryController extends CrudController
{
    public $model;
    public $cid = 0;
    public function __construct(Category $model)
    {
        $this->model = $model;
        
        if (!$this->cid) {
            $as = new MenuService;
            $this->cid = $as->categoryId('posts');
        }

        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
        ];

        $this->parse_columns = [
            ['name' => 'titlepic', 'type' => 'image', 'default' => ''],
            ['name' => 'state', 'default' => 0],
            ['name' => 'parent_id', 'type' => '', 'default' => $this->cid],
        ];

    }

    public function index()
    {
        //修改获取分类模式 直接递归 查询数据库获取数据
        //$search = [];
        $this->parseWiths($search);
        $data = $this->model->getChild($this->cid,[],function($item){
            $this->parseData($item,'decode','list');
            return $item;
        });
        
        return ['success' => true, 'msg' => '', 'data' => $data, 'search' => $search];

    }
}
