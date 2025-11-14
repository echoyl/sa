<?php

namespace Echoyl\Sa\Http\Controllers\admin\workflow;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\workflow\Log;

// customer namespace start

// customer namespace end

class LogController extends CrudController
{
    public $with_column = ['node'];

    public function __construct()
    {
        $this->model = new Log;

        $this->parse_columns = [];

    }
    // customer code start

    // customer code end
}
