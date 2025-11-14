<?php

namespace Echoyl\Sa\Http\Controllers\admin\workflow;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\workflow\Workflow;

// customer namespace start

// customer namespace end

class WorkflowController extends CrudController
{
    public $with_column = ['nodes'];

    public function __construct()
    {
        $this->model = new Workflow;

        $this->parse_columns = [];

    }

    // customer code start
    public function beforePost(&$data, $id = 0) {}
    // customer code end
}
