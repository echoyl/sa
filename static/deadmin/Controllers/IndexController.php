<?php

namespace App\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\admin\IndexController as AdminIndexController;

/**
 * @property \App\Services\deadmin\AdminAppService $service
 */
class IndexController extends AdminIndexController
{
    public $service;

    public function simplePanel()
    {
        return $this->success($this->service->simplePanel());
    }
}
