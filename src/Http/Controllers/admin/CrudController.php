<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use App\Http\Controllers\Controller;

class CrudController extends Controller
{
	var $model;
	var $cateModel;
	var $cid = 0;
	var $with_colunm = [];
	var $dont_post_colunms = [];//多余字段不需要提交数据库
	var $default_post = [];
	var $json_colunms = [];
	var $displayorder =[];
	var $can_be_null_colunms = [];//可以设置为空的字段
	var $with_count = [];
    public function index()
    {
        $psize = request('limit',10);
		$page = request('page',1);
		$search = [];
		if(method_exists($this,'handleSearch'))
		{
			[$m,$search] = $this->handleSearch();
		}else
		{
			$m = $this->model;
		}
	
		

		if(request('actype') == 'search')
		{
			return ['code'=>0,'msg'=>'','search'=>$search];	
		}

		$count = $m->count();
		if(!empty($this->with_colunm))
		{
			$m = $m->with($this->with_colunm);
		}
		if(!empty($this->with_count))
		{
			$m = $m->withCount($this->with_count);
		}
		if(!empty($this->displayorder))
		{
			foreach($this->displayorder as $val)
			{
				$m = $m->orderBy($val[0],$val[1]);
			}
		}
		$list = $m->orderBy('id','desc')
				->offset(($page-1)*$psize)
				->limit($psize)
				->get();
		
		if(method_exists($this,'listData'))
		{
			$this->listData($list);
		}
		
		return ['code'=>0,'msg'=>'','count'=>$count,'data'=>$list,'search'=>$search];	

    }

    public function show()
    {
        return $this->post();
    }

    public function store()
    {
        return $this->post();
	}
	
    public function post()
    {
		//sleep(10);
		$id = request('id',0);
		$m = $this->model;
		if(!empty($this->with_colunm))
		{
			$m = $m->with($this->with_colunm);
		}
		$item = $m->where(['id'=>$id])->first();
		
		if (!empty($item)) {
			
		}else
		{
			$item = $this->default_post;//数据的默认值
			$item['created_at'] = now();
		}
		
		if(method_exists($this,'postData'))
		{
			$this->postData($item);//postData为预处理数据格式
		}


		$type = request('actype');
		
		if (request()->isMethod('post')) 
		{
			switch($type)
			{
				case 'status':
					$data = ['status'=>intval(request('status'))];
				break;
				case 'displayorder':
					$data = ['displayorder'=>intval(request('displayorder'))];
				break;
				default:
				//d($this->can_be_null_colunms);
					$data = filterEmpty(request('base'),$this->can_be_null_colunms);//后台传入数据统一使用base数组，懒得每个字段赋值

					//设置不需要提交字段
					if(!empty($this->dont_post_colunms))
					{
						foreach($this->dont_post_colunms as $c)
						{
							if(isset($data[$c]))
							{
								unset($data[$c]);
							}
						}
					}
					//json数据列
					if(!empty($this->json_colunms))
					{
						foreach($this->json_colunms as $c)
						{
							if(isset($data[$c]))
							{
								$data[$c] = json_encode($data[$c]);
							}
						}
					}

					if(method_exists($this,'beforePost'))
					{
						$this->beforePost($data,$id);//操作前处理数据
					}

			}
			//d($data);
			if(!empty($id)) 
			{
				$this->model->where(['id'=>$id])->update($data);
			}else
			{
				$data['created_at'] = now();
				$id = $this->model->insertGetId($data);
			}
			if(method_exists($this,'afterPost'))
			{
				$this->afterPost($id);//数据更新或插入后的 补充操作
			}
			return ['code'=>0,'msg'=>''];
		}
		$category_arr = [];
		if($this->cateModel)
		{
			$category_arr = $this->cateModel->format(0,'');
		}
		$item['categoryarr'] = json_encode($category_arr);

		//json数据列
		if(!empty($this->json_colunms))
		{
			foreach($this->json_colunms as $c)
			{
				if(isset($item[$c]) && $item[$c])
				{
					$item[$c] = json_decode($item[$c],true);
				}else
				{
					$item[$c] = [];
				}
			}
		}

		return ['code'=>0,'msg'=>'','data'=>$item];
    }

	public function destroy()
	{
		$ids = request('ids','');
		if (!empty($ids)) {
			$ids = explode('.',$ids);
			$items = $this->model->whereIn('id',$ids)->get();
			foreach($items as $val)
			{
				$val->delete();
			}
			return ['code'=>0,'msg'=>'success'];
		}
		return ['code'=>1,'msg'=>'参数错误'];
	}
	public function copyOne()
	{
		$id = request('id',0);
		$item = $this->model->where(['id'=>$id])->first();
		if(!$item)
		{
			return ['code'=>1,'msg'=>'操作失败'];
		}
		$data = $item->toArray();
		unset($data['id']);
		$data['title'] .= '-副本';
		$this->model->insert($data);
		return ['code'=>0,'msg'=>'复制成功'];
	}
}
