<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\User;

class UserController extends CrudController
{
    //
	var $model;
	
	//var $json_columns = ['perms2'];
    public function __construct()
	{
		parent::__construct();
		$this->can_be_null_columns = ["desc","perms2"];
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

	public function destroy()
    {
        $id = request('id', 0);
        if ($id) {
            if (!is_array($id)) {
                $id = [$id];
            }
        }
        if (!empty($id)) {
            $m = $this->beforeDestroy($this->model->whereIn('id', $id));

            $items = $m->get();
            foreach ($items as $val) {
				if($val['id'] == 1)
				{
					return $this->fail([1,'admin 不能删除']);
				}
                $val->delete();
            }
            return $this->success('操作成功');
        }
		return $this->success('参数错误');
    }
}
