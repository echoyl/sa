<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\Role;
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
		$roles = $this->model->get()->toArray();
		$role_perms = [];
		foreach($roles as $val)
		{
			$role_perms[$val['id']] = $val['perms2']?explode(',',$val['perms2']):[];
		}

		$as = new MenuService;
		$data = [];
		$data['perms'] = $as->perms();
		//$item['perms'] = $ps->parsePerms();

		//$item['user_perms'] = isset($item['perms2']) && $item['perms2']?explode(',',$item['perms2']):[];
		$data['role_perms'] = $role_perms;
		return $this->success($data);
	}
    
}
