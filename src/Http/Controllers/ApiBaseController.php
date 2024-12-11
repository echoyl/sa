<?php

namespace Echoyl\Sa\Http\Controllers;

use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;
use Echoyl\Sa\Constracts\SaServiceInterface;
use Echoyl\Sa\Helpers\ApiResponse;
use Echoyl\Sa\Helpers\VerifyRequestInput;

/**
 * @property \Echoyl\Sa\Services\AdminAppService                $service
 */
class ApiBaseController extends Controller
{
    // API接口响应
    use ApiResponse,VerifyRequestInput;
    var $service;
    var $is_admin = true;
    public function __construct()
    {
        if($this->is_admin)
        {
            $this->service = app()->make(SaAdminAppServiceInterface::class);
        }else
        {
            $this->service = app()->make(SaServiceInterface::class);
        }
    }
}