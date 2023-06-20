<?php
namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;

class CategoryController extends CrudController
{
    //
	var $model;
	var $cid = 0;
    public function __construct(Category $model)
	{
		$this->model = $model;
		$post_parent_id = request('parent_id',0);
		$this->default_post = [
			'parent_id'=>$post_parent_id?:$this->cid
		];

	}

    public function index()
    {
		//修改获取分类模式 直接递归 查询数据库获取数据

		//return ['code'=>0,'msg'=>'','data'=>$this->model->getChild($this->cid)];	
		$search = [];
        $this->parseWiths($search);
		$data = $this->model->getChild($this->cid,[],function($val){
			$this->parseData($val, 'decode', 'list');
			return $val;
		});
		return $this->list($data,count($data),$search);
	}

	

}
