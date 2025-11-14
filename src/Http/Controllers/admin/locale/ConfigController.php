<?php

namespace Echoyl\Sa\Http\Controllers\admin\locale;

use Echoyl\Sa\Http\Controllers\admin\CategoryBaseController;
use Echoyl\Sa\Models\locale\Config;

// customer namespace start

// customer namespace end

class ConfigController extends CategoryBaseController
{
    public $cid = 0;
    // customer property start

    // customer property end
    public function __construct()
    {
        $this->with_column = [
            'category',
        ];
        $this->model = new Config;
        $post_parent_id = request('parent_id', 0);
        $this->default_post = [
            'parent_id' => $post_parent_id ?: $this->cid,
            'displayorder' => 0,
        ];
    }

    // customer code start

    // customer code end

}
