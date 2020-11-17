<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Category;

class CategoryController extends CrudController
{
    //
    var $model;
    public function __construct(Category $model)
	{
		$this->model = $model;

		$this->default_post = [
			'parent_id'=>request('parent_id',0)
		];

	}


    public function index()
    {
		$ids = $this->model->childrenIds(request('parent_id',0),false);
		//d($ids);
		$list = $this->model->whereIn('id',$ids)->orderBy('parent_id','asc')->orderBy('displayorder','desc')->get();
		return ['code'=>0,'msg'=>'','data'=>$list];	

    }

}
