<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\Log;

class LogController extends CrudController
{
    //
	var $model;
	var $with_column = [];
	var $search_config = [
		["name" => "created_at","columns" => ["created_at"],"where_type" => "whereBetween"]
	];
    public function __construct(Log $model)
	{
		$this->model = $model;
		$this->with_column = [
			'user'=>function($q){
				$q->select(['id','username']);
			}
		];
	}

	public function beforePost($data, $id = 0)
	{
		return $this->fail([1,'操作日记不能更新']);
	}


	public function handleSearch($search = [])
	{
		$m = $this->model;

		
		
		$startTime = request('startTime','');
		$endTime = request('endTime','');

		if($startTime)
		{
			$m = $m->where([['created_at','>=',$startTime]]);
		}
		if($endTime)
		{
			$m = $m->where([['created_at','<=',date("Y-m-d H:i:s",strtotime($endTime)+3600*24-1)]]);
		}

		return [$m,$search];
	}

	public function beforeDestroy($m)
	{
		return $m->where([['id','=',0]]);
	}
    
}
