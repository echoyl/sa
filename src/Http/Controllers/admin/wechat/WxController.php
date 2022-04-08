<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\Wx;
use Echoyl\Sa\Services\WechatService;

class WxController extends CrudController
{
	var $with_column = ['wxapp'];

    public function __construct()
	{
		$this->model = new Wx();
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

	public function syncUser()
	{
		set_time_limit(0);
		WechatService::wxuserlist();
		return ['code'=>0,'msg'=>'同步成功'];
	}

	public function handleSearch()
	{
		$m = $this->model;

		$search = [];

		$keyword = request('nickname','');
		if($keyword)
		{
			$m = $m->where([['nickname','like','%'.urldecode($keyword).'%']]);

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
			$m = $m->where([['updated_at','>=',$startTime]]);
		}
		if($endTime)
		{
			$m = $m->where([['updated_at','<=',date("Y-m-d H:i:s",strtotime($endTime)+3600*24-1)]]);
		}


		return [$m,$search];

	}

}
