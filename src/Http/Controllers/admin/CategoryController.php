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
		$ids = $this->model->childrenIds($this->cid,false);

		$m = $this->model;

		[$m,$search] = $this->handleSearch();

		//d($ids);
		$list = $m->whereIn('id',$ids)->orderBy('parent_id','asc')->orderBy('displayorder','desc')->get();
		return ['code'=>0,'msg'=>'','data'=>$list];	

	}
	
	public function handleSearch()
	{
		$m = $this->model;

		$search = [];

		$keyword = request('keyword','');
		if($keyword)
		{
			$search['keyword'] = urldecode($keyword);
			$m = $m->where([['title','like','%'.urldecode($keyword).'%']]);

		}

		return [$m,$search];

	}

}
