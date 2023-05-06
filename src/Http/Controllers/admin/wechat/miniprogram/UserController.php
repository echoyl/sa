<?php
namespace Echoyl\Sa\Http\Controllers\admin\wechat\miniprogram;
use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\miniprogram\User;

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
}
