<?php

namespace Echoyl\Sa\Http\Controllers\admin\setting;

use App\Http\Controllers\Controller;
use Echoyl\Sa\Models\Sets;
use Echoyl\Sa\Services\SetsService;

class SetsController extends Controller
{
	var $model;
    public function __construct(Sets $model)
	{
		$this->model = $model;
	}

	
	public function base()
    {
		return (new SetsService)->post('base');
	}

}
