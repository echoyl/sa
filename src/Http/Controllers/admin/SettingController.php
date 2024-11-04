<?php

namespace Echoyl\Sa\Http\Controllers\admin;

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

	
	public function base()
    {
		return (new SetsService)->post('base');
	}

	public function web()
    {
		return (new SetsService)->post('web');
	}

	public function setting()
    {
		return (new SetsService)->post('setting');
	}

	public function system()
	{
		return (new SetsService)->post('system');
	}
}
