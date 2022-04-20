<?php

namespace Echoyl\Sa\Http\Controllers;

use App\Helper\ApiResponse;
use App\Helper\VerifyRequestInput;
use App\Http\Controllers\Controller;

class ApiBaseController extends Controller
{
    // API接口响应
    use ApiResponse,VerifyRequestInput;
}