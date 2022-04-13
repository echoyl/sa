<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use App\Http\Controllers\Controller;

class CrudController extends Controller
{
	var $model;
	var $with_column = [];
	var $dont_post_columns = [];//多余字段不需要提交数据库
	var $default_post = [];
	var $json_columns = [];
	var $displayorder =[];
	var $can_be_null_columns = [];//可以设置为空的字段
	var $with_count = [];
    public function index()
    {
        $psize = request('pageSize',10);
		$page = request('current',1);
		
		$search = [];
		if(method_exists($this,'handleSearch'))
		{
			[$m,$search] = $this->handleSearch();
		}else
		{
			$m = $this->model;
		}
		
		if(!isset($search['status']))
		{
			$search['status'] = [
				'1'=>['text'=>'启用','status'=>'success'],
				'0'=>['text'=>'禁用','status'=>'error']
			];
		}
		

		if(request('actype') == 'search')
		{
			return ['code'=>0,'msg'=>'','search'=>$search];	
		}

		$count = $m->count();
		if(!empty($this->with_column))
		{
			$m = $m->with($this->with_column);
		}
		if(!empty($this->with_count))
		{
			$m = $m->withCount($this->with_count);
		}
		$has_id = false;
		if(request('sort'))
		{
			//添加排序检测
			$sort = explode('.',request('sort'));
			if(count($sort) > 1 && $sort[1])
			{
				$m = $m->orderBy($sort[0],$sort[1]);
				$has_id = true;
			}
		}

		if(!empty($this->displayorder))
		{
			foreach($this->displayorder as $val)
			{
				$m = $m->orderBy($val[0],$val[1]);
			}
		}else
		{
			//默认按照id排序
			if(!$has_id)
			{
				$m = $m->orderBy('id','desc');
			}
			
		}
		$list = $m->offset(($page-1)*$psize)
				->limit($psize)
				->get();
		
		if(method_exists($this,'listData'))
		{
			$this->listData($list);
		}
		
		return ['code'=>0,'success'=>true,'msg'=>'','count'=>$count,'total'=>$count,'data'=>$list,'search'=>$search];	

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
		$id = $id?:request('base.id',0);
		$m = $this->model;
		if(!empty($this->with_column))
		{
			$m = $m->with($this->with_column);
		}
		$item = $m->where(['id'=>$id])->first();
		
		if (!empty($item)) {
			if(method_exists($this,'checkPost'))
			{
				$ret = $this->checkPost($item,$id);//编辑数据检测
				if($ret)
				{
					return $ret;
				}
			}
		}else
		{
			$item = $this->default_post;//数据的默认值
			$item['created_at'] = now()->toDateTimeString();
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
					$name = request('field','status');
					if($name == 'status')
					{
						$val = request('status');
					}else
					{
						$val = request('val');
					}
					$data = [$name=>$val];
				break;
				case 'displayorder':
					$data = ['displayorder'=>intval(request('displayorder'))];
				break;
				default:
				//d($this->can_be_null_columns);
					$data = filterEmpty(request('base'),$this->can_be_null_columns);//后台传入数据统一使用base数组，懒得每个字段赋值

					//设置不需要提交字段
					if(!empty($this->dont_post_columns))
					{
						foreach($this->dont_post_columns as $c)
						{
							if(isset($data[$c]))
							{
								unset($data[$c]);
							}
						}
					}
					//json数据列
					if(!empty($this->json_columns))
					{
						foreach($this->json_columns as $c)
						{
							if(isset($data[$c]))
							{
								$data[$c] = json_encode($data[$c]);
							}
						}
					}

					if(method_exists($this,'beforePost'))
					{
						$ret = $this->beforePost($data,$id);//操作前处理数据 如果返回数据表示 数据错误 返回错误信息
						if($ret)
						{
							return $ret;
						}
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
			$ret = null;
			if(method_exists($this,'afterPost'))
			{
				$ret = $this->afterPost($id);//数据更新或插入后的 补充操作
			}
			return $ret?:['code'=>0,'msg'=>'操作成功'];
		}
		
		//json数据列
		if(!empty($this->json_columns))
		{
			foreach($this->json_columns as $c)
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
		$id = request('id',0);
		if($id)
		{
			if(!is_array($id))
			{
				$id = [$id];
			}
		}
		if (!empty($id)) {
			$items = $this->model->whereIn('id',$id)->get();
			foreach($items as $val)
			{
				$val->delete();
			}
			return ['code'=>0,'msg'=>'删除成功'];
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
