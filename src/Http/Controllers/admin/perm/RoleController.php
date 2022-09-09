<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use App\Services\AdminMenuService;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\PermRole;
use Echoyl\Sa\Services\AdminService;
use Echoyl\Sa\Services\PermService;

class RoleController extends CrudController
{
    //
	var $model;
    public function __construct(PermRole $model)
	{
		$this->model = $model;
	}

	public function postData(&$item)
	{
		$ps = new PermService();
		$as = new AdminMenuService;

		//$item['perms'] = $ps->parsePerms();
		$item['perms'] = $as->perms();
		//$item['user_perms'] = isset($item['perms2']) && $item['perms2']?explode(',',$item['perms2']):[];

	}

	public function beforePost(&$data)
	{
		// if(isset($data['perms2']) && $data['perms2'])
		// {
		// 	$data['perms2'] = implode(',',$data['perms2']);
		// }
		return;
	}

	public function handleSearch()
	{
		$m = $this->model;


		$title = request('title','');
		$search = [];
		if($title)
		{
			$title = urldecode($title);
			$m = $m->where([['title','like','%'.$title.'%']]);

		}

		return [$m,$search];
	}
    
}
