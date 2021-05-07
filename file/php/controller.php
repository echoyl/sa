<?php
namespace App\Http\Controllers\admin$namespace$;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use App\Models$namespace$\$name$;

class $controller_name$ extends CrudController
{
	var $with_column = ['category'];

    public function __construct()
	{
		$this->model = new $name$();
	}

	public function postData(&$item)
	{

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

		$keyword = request('keyword','');
		if($keyword)
		{
			$m = $m->where([['title','like','%'.urldecode($keyword).'%']]);

		}

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

		return [$m,$search];

	}

}
