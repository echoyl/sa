<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Models\perm\Role;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\dev\MenuService;
use Echoyl\Sa\Services\PermService;

class UserController extends CrudController
{
    //
	var $model;
	
	//var $json_columns = ['perms2'];
	var $with_count = ['logs'];
	var $can_be_null_columns = ['desc'];
    public function __construct()
	{
		$this->model = new User;
		$this->with_column = ['role','logs'=>function($q){
			$q->orderBy('last_used_at','desc')->limit(1);
		}];
	}

	public function listData(&$list)
	{
		foreach($list as $key=>$val)
		{
			$list[$key]['password'] = '';
		}
	}

	public function handleSearch()
	{
		$m = $this->model;


		$username = request('username','');
		$search = [];
		if($username)
		{
			$username = urldecode($username);
			$m = $m->where([['username','like','%'.$username.'%']]);

		}

		$roleid = request('roleid','');
		if($roleid)
		{
			$m = $m->where('roleid',$roleid);
		}
		$m = $m->where([['id','!=',1]]);

		return [$m,$search];
	}

	public function beforePost(&$data,$id)
	{
		// if(isset($data['perms2']) && $data['perms2'])
		// {
		// 	$data['perms2'] = implode(',',$data['perms2']);
		// }
		if(isset($data['username']))
		{
			$has = $this->model->where(['username'=>$data['username']]);
			if($id)
			{
				$has = $has->where([['id','!=',$id]]);
			}
			$has = $has->first();
			if($has)
			{
				return $this->fail([1,'用户名已存在']);
			}
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

	public function beforeDestroy($m)
	{
		return $m->where([['id','!=',1]]);
	}
}
