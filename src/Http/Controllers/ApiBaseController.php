<?php

namespace Echoyl\Sa\Http\Controllers;

use Echoyl\Sa\Helpers\ApiResponse;
use Echoyl\Sa\Helpers\VerifyRequestInput;
use App\Http\Controllers\Controller;

class ApiBaseController extends Controller
{
    // API接口响应
    use ApiResponse,VerifyRequestInput;
}