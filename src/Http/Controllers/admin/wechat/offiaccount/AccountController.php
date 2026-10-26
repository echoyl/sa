<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Account;

// customer namespace start

// customer namespace end

class AccountController extends CrudController
{
    public function __construct()
    {
        parent::__construct();
        $this->with_column = [
            'menus',
        ];
        $this->can_be_null_columns = ['subscribe_reply', 'auto_reply'];
        $this->model = new Account;
        // customer construct start
        $this->displayorder = [['id', 'desc']];
        // customer construct end
    }

    // customer code start
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
