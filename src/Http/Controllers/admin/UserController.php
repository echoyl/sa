<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Models\PermUser;
use Echoyl\Sa\Models\Role;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\PermService;

class UserController extends CrudController
{
    //
	var $model;
	var $with_colunm = ['role'];
	var $json_colunms = ['perms2'];
	var $can_be_null_colunms = ['desc'];
    public function __construct(PermUser $model)
	{
		$this->model = $model;
		$this->cateModel = new Role();
		$perm = new PermService();
		$this->default_post = [
			'perms'=>$perm->formatPerms(),
			'roles'=>json_encode($this->cateModel->format()),
			'user_perms'=>[]
		];
	}

	public function handleSearch()
	{
		$m = $this->model;


		$keyword = request('keyword','');
		$search = [];
		if($keyword)
		{
			$search['keyword'] = urldecode($keyword);
			$m = $m->where([['username','like','%'.$search['keyword'].'%']]);

		}

		$roleid = request('roleid','');
		if($roleid)
		{
			$m = $m->where('roleid',$roleid);
		}
		$m = $m->where([['id','!=',1]]);
		return [$m,$search];
	}

	public function postData(&$item)
	{
		$item['perms'] = $this->default_post['perms'];
		$item['roles'] = $this->default_post['roles'];
		$item['user_perms'] = $item['perms2']?json_decode($item['perms2'],true):[];
	}

	public function afterPost($id)
	{
		$data = filterEmpty(request('base'));
		if(isset($data['password']))
		{
			$this->model->where(['id'=>$id])->update(['password'=>AdminService::pwd($data['password'])]);
		}
	}

	public function destroy()
	{
		$ids = request('ids','');
		if (!empty($ids)) {
			$ids = explode('.',$ids);
			$items = $this->model->whereIn('id',$ids)->get();
			foreach($items as $val)
			{
				if($val['id'] == 1)
				{
					continue;
				}
				$val->delete();
			}
			return ['code'=>0,'msg'=>'success'];
		}
		return ['code'=>1,'msg'=>'参数错误'];
	}
}
