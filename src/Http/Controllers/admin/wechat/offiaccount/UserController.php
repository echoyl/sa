<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Account;
use Echoyl\Sa\Models\wechat\offiaccount\User;
use Echoyl\Sa\Services\HelperService;
use Echoyl\Sa\Services\WechatService;
use Exception;


//customer namespace start

//customer namespace end

class UserController extends CrudController
{
	var $with_column = ["app"];
	var $search_config = [
		["name" => "appid","columns" => ["appid"],"where_type" => "="],
		["name" => "created_at","columns" => ["last_used_at"],"where_type" => "whereBetween"],
	];

    public function __construct()
	{
		$this->model = new User();

		$this->parse_columns = [];

	}
	//customer code start
	
	//customer code end


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
