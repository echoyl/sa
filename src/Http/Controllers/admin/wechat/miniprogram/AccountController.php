<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\miniprogram;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\miniprogram\Account;
use Override;

class AccountController extends CrudController
{
    public function __construct()
    {
        $this->model = new Account;

        $this->parse_columns = [];

    }

    // customer code start
    #[Override]
    public function handleSearch($search = [])
    {
        $m = $this->getModel();

        $select = request('select');
        if (! empty($select)) {
            $this->select_columns = $select;
        }

        return [$m, $search];
    }
    // customer code end
}
