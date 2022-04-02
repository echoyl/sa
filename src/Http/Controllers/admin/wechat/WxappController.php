<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\Wxapp;

class WxappController extends CrudController
{
	//var $with_column = ['category'];

    public function __construct()
	{
		$this->model = new Wxapp();
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

		$nickName = request('nickName','');
		if($nickName)
		{
			$m = $m->where([['nickName','like','%'.urldecode($nickName).'%']]);

		}

		$status = request('status','');
		if($status !== '')
		{
			$m = $m->where('status',$status);

		}
		
		$startTime = request('startTime','');
		$endTime = request('endTime','');

		if($startTime)
		{
			$m = $m->where([['last_used_at','>=',$startTime]]);
		}
		if($endTime)
		{
			$m = $m->where([['last_used_at','<=',date("Y-m-d H:i:s",strtotime($endTime)+3600*24-1)]]);
		}

		$search['status'] = [
			'1'=>['text'=>'启用','status'=>'success'],
			'0'=>['text'=>'禁用','status'=>'error']
		];

		return [$m,$search];

	}

}
