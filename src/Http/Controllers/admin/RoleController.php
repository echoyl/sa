<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Models\Role;


class RoleController extends CrudController
{
    //
	var $model;
    public function __construct(Role $model)
	{
		$this->model = $model;
	}

    
}
