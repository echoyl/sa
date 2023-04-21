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
	var $withs = [
        ['name'=>'role','class'=>Role::class,'cid'=>0]
    ];
    public function __construct(User $model)
	{
		$this->model = $model;
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

	public function postData(&$item)
	{
		$ps = new PermService();

		$roles = PermRole::get();
		$role_perms = [];
		foreach($roles as $val)
		{
			$role_perms[$val['id']] = $val['perms2']?explode(',',$val['perms2']):[];
		}

		$as = new MenuService;
		$item['perms'] = $as->perms();
		//$item['perms'] = $ps->parsePerms();

		$item['user_perms'] = isset($item['perms2']) && $item['perms2']?explode(',',$item['perms2']):[];
		$item['role_perms'] = $role_perms;
		$item['password'] = '';
	}

	public function beforePost(&$data)
	{
		// if(isset($data['perms2']) && $data['perms2'])
		// {
		// 	$data['perms2'] = implode(',',$data['perms2']);
		// }
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
