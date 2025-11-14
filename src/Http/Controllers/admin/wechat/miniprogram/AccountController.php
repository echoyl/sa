<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\miniprogram;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\miniprogram\Account;

class AccountController extends CrudController
{
    public function __construct()
    {
        $this->model = new Account;

        $this->parse_columns = [];

    }
    // customer code start

    // customer code end
}
