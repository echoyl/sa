<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\Log;

class LogController extends CrudController
{
    //
	var $model;
	var $with_column = [];
    public function __construct(Log $model)
	{
		$this->model = $model;
		$this->with_column = [
			'user'=>function($q){
				$q->select(['id','username']);
			}
		];
	}


	public function handleSearch()
	{
		$m = $this->model;

		$search = [];
		
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

    
}
