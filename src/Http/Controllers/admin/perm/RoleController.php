<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\PermRole;
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

	public function handleSearch()
	{
		$m = $this->model;


		$keyword = request('keyword','');
		$search = [];
		if($keyword)
		{
			$search['keyword'] = urldecode($keyword);
			$m = $m->where([['title','like','%'.$search['keyword'].'%']]);

		}

		return [$m,$search];
	}
    
}
