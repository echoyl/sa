<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat;

use App\Http\Controllers\Controller;
use Echoyl\Sa\Models\wechat\Sets;
use Echoyl\Sa\Services\SetsService;

class SetsController extends Controller
{
	var $model;
	var $service;
    public function __construct()
	{
		$this->model = new Sets();
		$this->service = new SetsService($this->model);
	}

	
	public function wxconfig()
    {
		return $this->service->post('wxconfig');
	}

	public function wxappconfig()
    {
		return (new SetsService($this->model))->post('wxappconfig');
	}

	public function wxpayconfig()
    {
		return (new SetsService($this->model))->post('wxpayconfig');
	}

}
