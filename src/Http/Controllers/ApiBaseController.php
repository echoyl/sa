<?php

namespace Echoyl\Sa\Http\Controllers;

use Echoyl\Sa\Helpers\ApiResponse;
use Echoyl\Sa\Helpers\VerifyRequestInput;
use Echoyl\Sa\Services\HelperService;

/**
 * @property \Echoyl\Sa\Services\AdminAppService $service
 */
class ApiBaseController extends Controller
{
    // API接口响应
    use ApiResponse,VerifyRequestInput;

    public $service;

    public $is_admin = true;

    public function __construct()
    {
        if ($this->is_admin) {
            $this->service = HelperService::getAdminService();
        } else {
            $this->service = HelperService::getAppService();
        }
    }
}
