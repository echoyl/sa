<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\PermLog;

class LogController extends CrudController
{
    //
	var $model;
	var $with_column = [];
    public function __construct(PermLog $model)
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
		
		$sdate = request('sdate','');

		if($sdate)
		{
			$sdate = urldecode($sdate);
			$date_range = explode(' - ',$sdate);
			$m = $m->whereBetween('created_at',[$date_range[0],$date_range[1].' 23:59:59']);
			$search['sdate'] = $sdate;
		}

		$search['status'] = [
			['title'=>'启用','id'=>1],['title'=>'禁用','id'=>0],
		];

		return [$m,$search];
	}

    
}
