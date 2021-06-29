<?php
namespace App\Http\Controllers\admin$namespace$;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use App\Models$modelnamespace$\$modelname$;
$relation_models$

class $controller_name$ extends CrudController
{
	var $with_column = $with_column$;

    public function __construct()
	{
		$this->model = new $modelname$();
	}
	
	public function listData(&$list)
	{
		/*
		foreach($list as $key=>$val)
		{
			
		}
		*/
		return;
	}

	public function postData(&$item)
	{
		$relations$
		return;
	}

	/**
	 * 编辑数据时 检测数据合法性
	 *
	 * @param [type] $item
	 * @return void
	 */
	public function checkPost($item)
	{
		if(false)
		{
			return ['code'=>1,'msg'=>'信息错误，请重试'];
		}
        return;
	}

	/**
	 * 提交数据时检测数据合法性
	 *
	 * @param [type] $data
	 * @param [type] $id
	 * @return void
	 */
	public function beforePost($data,$id = 0)
	{
		if(false)
		{
			return ['code'=>1,'msg'=>'数据错误，请重试'];
		}
        return;
	}

	public function handleSearch()
	{
		$m = $this->model;

		$search = [];

		$search$

		$status = request('status','');
		if($status !== '')
		{
			$m = $m->where('status',$status);

		}
		
		$sdate = request('sdate','');

		if($sdate)
		{
			$m = $m->whereBetween('created_at',[$sdate,date("Y-m-d H:i:s",strtotime($sdate)+3600*24-1)]);
			$search['sdate'] = $sdate;
		}

		$search['status'] = [
			['title'=>'启用','id'=>1],['title'=>'禁用','id'=>0],
		];

		$search_relations$

		return [$m,$search];

	}

}
