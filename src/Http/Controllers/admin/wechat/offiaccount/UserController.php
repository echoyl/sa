<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Account;
use Echoyl\Sa\Models\wechat\offiaccount\User;
use Echoyl\Sa\Services\HelperService;
use Echoyl\Sa\Services\WechatService;
use Exception;


class UserController extends CrudController
{
	//var $with_column = ['category'];

	public function __construct()
	{
		$this->model = new User();

		$this->withs = [
			['name' => 'account', 'data' => (new Account())->select(['id as value', 'appname as label'])->get()],
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
		if (false) {
			return ['code' => 1, 'msg' => '信息错误，请重试'];
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
	public function beforePost($data, $id = 0)
	{
		if (false) {
			return ['code' => 1, 'msg' => '数据错误，请重试'];
		}
		return;
	}

	public function handleSearch()
	{
		$m = $this->model;

		$search = [];

		$nickname = request('nickname', '');
		if ($nickname) {
			$m = $m->where([['nickname', 'like', '%' . urldecode($nickname) . '%']]);
		}

		$startTime = request('lastStartTime', '');
		$endTime = request('lastEndTime', '');

		if ($startTime) {
			$m = $m->where([['last_used_at', '>=', $startTime]]);
		}
		if ($endTime) {
			$m = $m->where([['last_used_at', '<=', date("Y-m-d H:i:s", strtotime($endTime) + 3600 * 24 - 1)]]);
		}

		$account_id = request('account_id', '');
		if($account_id)
		{
			$account = (new Account())->where(['id'=>$account_id])->first();
			if($account)
			{
				$m = $m->where(['appid'=>$account['appid']]);
			}
		}

		return [$m, $search];
	}

	public function syncUser()
	{
		$url = url(env('APP_PREFIX', '') . env('APP_ADMIN_PREFIX', '') . '/wechat/offiaccount/user/_syncUser');
		HelperService::asynUrl($url,['account_id'=>request('account_id',0)]);
		return ['code' => 0, 'msg' => '已提交计划任务，正在同步中'];
	}

	public function _syncUser()
	{
		set_time_limit(0);
		$ret = WechatService::wxuserlist(null, request('account_id', 0));
		return $ret;
	}
}
