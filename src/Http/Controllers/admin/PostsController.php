<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Attachment;
use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\Posts;

class PostsController extends CrudController
{
    //
	var $with_column = ['category'];
	var $dont_post_columns = ['pics_ids','files'];
	//var $json_columns = ['specs'];
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
		//$item['category_arr'] = json_encode($this->cateModel->format($this->cid));
		$item['spec_arr'] = $this->spec_arr?json_encode($this->spec_arr):false;
		return;
	}

	/**
	 * 编辑数据时 检测数据合法性
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function checkPost($item)
	{
		//检测分类id是否在当前cid的子分类下
		$self_ids = $this->cateModel->childrenIds($this->cid);
		if(!in_array($item['category_id'],$self_ids))
		{
			return ['code'=>1,'msg'=>'分类信息错误，请重试'];
		}
	}

	/**
	 * 提交数据时检测数据合法性
	 *
	 * @param [type] $data
	 * @param [type] $id
	 * @return void
	 */
	public function beforePost(&$data,$id = 0)
	{
		if(isset($data['category_id']))
		{
			$self_ids = $this->cateModel->childrenIds($this->cid);
			if(!in_array($data['category_id'],$self_ids))
			{
				return ['code'=>1,'msg'=>'分类信息错误，请重试'];
			}
		}
	}

	public function handleSearch()
	{
		$m = $this->model;

		$search = [];

		$keyword = request('keyword','');
		if($keyword)
		{
			$m = $m->where([['title','like','%'.urldecode($keyword).'%']]);

		}

		$status = request('status','');
		if($status !== '')
		{
			$m = $m->where('status',$status);

		}

		//$search['category_arr'] = json_encode($this->cateModel->format($this->cid));
		$search['categorys'] = $this->cateModel->format($this->cid);
		$category_id = request('category_id','');
		$category_id = $category_id?:request('categorys','');
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
		$search['status'] = [
			['title'=>'启用','id'=>1],['title'=>'禁用','id'=>0],
		];

		return [$m,$search];

	}

}
