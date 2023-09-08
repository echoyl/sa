<?php

namespace Echoyl\Sa\Http\Controllers\admin;

use Echoyl\Sa\Http\Controllers\ApiBaseController;
use Echoyl\Sa\Services\CasService;

class HelperController extends ApiBaseController
{
    public function pca()
    {
        $cs = new CasService;
        return $this->success($cs->cascader(request('level'),request('topCode','')));
    }
}
