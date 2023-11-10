<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\Role;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\dev\MenuService;

class RoleController extends CrudController
{
    //
	var $model;
    public function __construct(Role $model)
	{
		$this->model = $model;
	}


	public function perms()
	{
		$roleid = request('roleid');
		$enable = [];
		$data = [];
		if($roleid)
		{
			$role = $this->model->where(['id'=>$roleid])->first();
			if($role)
			{
				$enable = explode(',',$role['perms2']);
				$data['role_perms2'] = $enable;
			}
		}
		$as = new MenuService;
		[$perms] = $as->perms(0,$enable);
		$data['perms'] = $perms;
		return $this->success($data);
	}

	public function afterPost($id, $data)
	{
		if($data['sync_user'])
		{
			//同步该角色所有用户的权限
			$update = [
				'perms2'=>$data['perms2']
			];
			AdminService::getUserModel()->where(['roleid'=>$data['id']])->update($update);
		}
	}
    
}
