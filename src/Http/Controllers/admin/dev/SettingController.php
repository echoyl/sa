<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Models\Setting;
use Echoyl\Sa\Services\dev\utils\Utils;
use Echoyl\Sa\Services\SetsService;

class SettingController extends ApiBaseController
{
	var $model;
	public function __construct(Setting $model)
	{
		$this->model = $model;
	}

	public function setting()
	{
		//设置系统设置中的菜单，主要可以自动检索出菜单中的图片字段信息
		request()->offsetSet('dev_menu', Utils::$setting_dev_menu);
		return (new SetsService)->post('setting');
		//return (new SetsService)->post('setting',[['logo','image'],['loginBgImage','image']]);
	}
}
