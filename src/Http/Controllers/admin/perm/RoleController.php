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
		$this->can_be_null_columns = ["desc","perms2"];
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
		}else
		{
			//初始化时检测当前用户的角色信息
			if(!AdminService::isSuper())
			{
				$enable = explode(',',AdminService::user()->perms2);
				if(empty($enable))
				{
					$enable = [0];
				}
			}
		}
		$as = new MenuService;
		//这里获取当前角色的权限，无权限项disable 设置为true
		[$perms] = $as->perms(0,$enable);
		$data['perms'] = $perms;
		return $this->success($data);
	}

	public function beforePost(&$data, $id = 0, $item = [])
	{
		if(!AdminService::isSuper())
		{
			if(isset($data['perms2']))
			{
				//一般用户需要和传的数据进行对比，如果传的数据中包含超过了当前权限的数据，则剔除
				$enable = explode(',',AdminService::user()->perms2);
				$data['perms2'] = implode(',',array_intersect(explode(',',$data['perms2']),$enable));
			}
		}
		return;
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
