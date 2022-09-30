<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat\miniprogram;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\miniprogram\Account;
use Echoyl\Sa\Models\wechat\miniprogram\User;

class UserController extends CrudController
{
	//var $with_column = ['category'];

    public function __construct()
	{
		$this->model = new User();

		$this->withs = [
            ['name' => 'account', 'data' => (new Account())->select(['id as value','appname as label'])->get()],
        ];
	
		$this->with_column = ['account'];

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

		$nickname = request('nickname','');
		if($nickname)
		{
			$m = $m->where([['nickname','like','%'.urldecode($nickname).'%']]);

		}
		
		$startTime = request('lastStartTime','');
		$endTime = request('lastEndTime','');

		if($startTime)
		{
			$m = $m->where([['last_used_at','>=',$startTime]]);
		}
		if($endTime)
		{
			$m = $m->where([['last_used_at','<=',date("Y-m-d H:i:s",strtotime($endTime)+3600*24-1)]]);
		}

		$account_id = request('account_id','');
		if($account_id)
		{
			$m = $m->where(['account_id'=>$account_id]);

		}

		return [$m,$search];

	}

}
