<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\Role;
use Echoyl\Sa\Services\PermService;

class RoleController extends CrudController
{
    //
	var $model;
    public function __construct(Role $model)
	{
		$this->model = $model;
	}

	public function postData(&$item)
	{
		$perm = new PermService();


		$item['perms'] = $perm->formatPerms();
		$item['user_perms'] = isset($item['perms2']) && $item['perms2']?explode(',',$item['perms2']):[];

	}

	public function beforePost(&$data)
	{
		if(isset($data['perms2']) && $data['perms2'])
		{
			$data['perms2'] = implode(',',$data['perms2']);
		}
		return;
	}

    
}
