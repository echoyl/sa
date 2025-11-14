<?php

namespace Echoyl\Sa\Http\Controllers\admin\workflow;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\workflow\Node;

// customer namespace start

// customer namespace end

class NodeController extends CrudController
{
    public $with_column = ['upstream', 'downstream'];

    public $search_config = [
        ['name' => 'workflow_id', 'columns' => ['workflow_id'], 'where_type' => '='],
    ];

    public function __construct()
    {
        $this->model = new Node;

        $this->parse_columns = [];

    }

    // customer code start
    public $displayorder = [['id', 'asc']];

    public function postData(&$item)
    {
        $workflow_id = request('workflow_id');
        if ($workflow_id && ! isset($item['workflow_id'])) {
            $item['workflow_id'] = $workflow_id;
        }
    }

    public function beforePost(&$data, $id = 0)
    {
        // $workflow_id = request('workflow_id');
        // if($workflow_id)
        // {
        // 	$data['workflow_id'] = $workflow_id;
        // }
    }
    // customer code end
}
