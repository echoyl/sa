<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Services\HelperService;

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

	var $parse_columns = [
        ['name'=>'state','type'=>'state','default'=>'enable']
    ];
	var $withs = [];

	public function defaultSearch($m)
	{
		$category_id = request('category_id');
        if(!empty($category_id))
        {
            $category_id = array_pop($category_id);
            $m = $m->where(['category_id'=>$category_id]);
        }


		$title = request('title','');
		if($title)
		{
			$m = $m->where([['title','like','%'.urldecode($title).'%']]);

		}

		$state = request('state','');
		if($state !== '')
		{
			$m = $m->where('state',$state);

		}

        $startTime = request('startTime','');
		$endTime = request('endTime','');

		if($startTime)
		{
			$m = $m->where([['created_at','>=',$startTime]]);
		}
		if($endTime)
		{
			$m = $m->where([['created_at','<=',date("Y-m-d H:i:s",strtotime($endTime)+3600*24-1)]]);
		}
		return $m;
	}

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

		$this->parseWiths($search);

		$m = $this->defaultSearch($m);

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
		$sort_type = ['descend'=>'desc','ascend'=>'asc'];
		if(request('sort'))
		{
			//添加排序检测
			$sort = json_decode(request('sort'),true);
			if(!empty($sort))
			{
				foreach($sort as $skey=>$sval)
				{
					$m = $m->orderBy($skey,$sort_type[$sval]??'desc');
				}
				
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

		foreach($list as $key=>$val)
		{
			$this->parseData($val,'decode','list');
			$list[$key] = $val;
		}
		
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

					$this->parseData($data);

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
		}else
		{
			$this->parseData($item,'decode');
			$this->parseWiths($item);
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

	public function parseWiths(&$data)
    {
        $ret = [];
        foreach($this->withs as $with)
        {
            $name = $with['name'].'s';
            $data[$name] = (new $with['class'])->format($with['cid']);
            $ret[] = $with['name'];
        }
        $data['states'] = [
            'enable'=>['text'=>'启用','status'=>'success'],
            'disable'=>['text'=>'禁用','status'=>'error']
        ];
        return $ret;
    }

	public function parseData(&$data,$in = 'encode',$from = 'detail')
    {
        foreach($this->parse_columns as $col)
        {
            $name = $col['name'];
            $type = $col['type'];

            $data[$name] = $data[$name]??$col['default'];

            switch($type)
            {
                case 'image':
                    $data[$name] = HelperService::uploadParse($data[$name]??'',$in == 'encode'?true:false);
                    break;
                case 'cascader':
                    $_name = '_'.$name;
                    if($in == 'encode')
                    {
                        if(!empty($data[$name]))
                        {
                            $data[$_name] = json_encode($data[$name]);
                            $data[$name] = array_pop($data[$name]);
                        }
                    }else
                    {
                        $data[$name] = isset($data[$_name]) && $data[$_name]?json_decode($data[$_name],true):[];
                    }
                break;
                case 'state':
                    if($in == 'encode')
                    {
                        $data[$name] = !$data[$name] || $data[$name] == 'disable'?'disable':'enable';
                    }else
                    {
                        if($from == 'detail')
                        {
                            $data[$name] = $data[$name] == 'enable'?true:false;
                        }
                    }
                break; 
            }
        }
        return;
    }

}
