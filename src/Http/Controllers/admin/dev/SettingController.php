<?php

namespace Echoyl\Sa\Http\Controllers\admin\dev;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Models\Setting;
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
		return (new SetsService)->post('setting',[['logo','image'],['loginBgImage','image']]);
	}
}
