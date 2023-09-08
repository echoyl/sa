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
	var $can_be_null_columns = ['desc'];
    public function __construct()
	{
		parent::__construct();
		$this->with_column = [
		    'role',
		    'log' => function($q0){$q0->select(["id","last_used_at","name","tokenable_id"]);},
		];
		$this->uniqueFields = ['username','mobile'];
		$this->search_config = [
		    [
		        'name' => 'username',
		        'columns' => [
		            'username',
		            'mobile',
		        ],
		        'where_type' => 'like',
		    ],
		    [
		        'name' => 'roleid',
		        'columns' => [
		            'roleid',
		        ],
		        'where_type' => '=',
		    ],
		];
		$this->model = new User;
		
	}
}
