<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\PermUser;
use Echoyl\Sa\Models\Role;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\PermService;

class UserController extends CrudController
{
    //
	var $model;
	
	//var $json_columns = ['perms2'];
	var $with_count = ['logs'];
	var $can_be_null_columns = ['desc'];
    public function __construct(PermUser $model)
	{
		$this->model = $model;
		$this->with_column = ['role','logs'=>function($q){
			$q->orderBy('last_used_at','desc')->limit(1);
		}];
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

		$search['roles'] = Role::select(['title as name','id'])->get()->toArray();

		return [$m,$search];
	}

	public function postData(&$item)
	{
		$perm = new PermService();

		$roles = Role::get();
		$_roles = [];
		$role_perms = [];
		foreach($roles as $val)
		{
			$_roles[] = ['id'=>$val['id'],'name'=>$val['title']];
			$role_perms[$val['id']] = $val['perms2']?explode(',',$val['perms2']):[];
		}

		$item['perms'] = $perm->formatPerms();
		$item['roles'] = json_encode($_roles);
		$item['user_perms'] = isset($item['perms2']) && $item['perms2']?explode(',',$item['perms2']):[];
		$item['role_perms'] = $role_perms;
		$item['password'] = '';
	}

	public function beforePost(&$data)
	{
		if(isset($data['perms2']) && $data['perms2'])
		{
			$data['perms2'] = implode(',',$data['perms2']);
		}
		return;
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
