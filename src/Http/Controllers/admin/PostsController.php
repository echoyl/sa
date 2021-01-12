<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Attachment;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\Posts;

class PostsController extends CrudController
{
    //
	var $with_colunm = ['category'];
	var $dont_post_colunms = ['pics_ids','files'];
	//var $json_colunms = ['specs'];
	var $cid = 0;
	var $spec_arr = false;
    public function __construct()
	{
		$this->model = new Posts();
		$this->cateModel = new Category();
		//$this->cid = intval(request('page_info.id'));
	}

	public function postData(&$item)
	{
		//$item['files'] = json_encode((new Attachment)->getAttachment($item['files_ids']));
		$item['category_arr'] = json_encode($this->cateModel->format($this->cid));
		$item['spec_arr'] = $this->spec_arr?json_encode($this->spec_arr):false;
		return;
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

		$search['category_arr'] = json_encode($this->cateModel->format($this->cid));
		$search['categorys'] = $this->cateModel->format($this->cid);
		$category_id = request('category_id','');

		$self_ids = $this->cateModel->childrenIds($this->cid);//自身包含的子分类id集合
		//d($self_ids);
		if($category_id)
		{
			if(!in_array($category_id,$self_ids))
			{
				$category_id = '';
			}else
			{
				$self_ids = $this->cateModel->childrenIds($category_id);

				$search['category_id'] = $category_id;

			}

		}

		
		
		$m = $m->whereIn('category_id',$self_ids);
		
		// $sdate = request('sdate','');
		// if($sdate)
		// {
		// 	//$params[] = ['title','like','%'.$keyword.'%'];
		// 	$m = $m->whereBetween('created_at',[$sdate,date("Y-m-d H:i:s",strtotime($sdate)+3600*24-1)]);
		// 	$search['sdate'] = $sdate;
		// }


		return [$m,$search];

	}

}
