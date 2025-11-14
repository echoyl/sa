<?php

namespace Echoyl\Sa\Models\workflow;

use Echoyl\Sa\Models\Base;

class Workflow extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'workflow_workflow';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                ['name' => 'state', 'type' => 'switch', 'default' => 1],
                ['name' => 'config', 'type' => 'json', 'default' => ''],
            ];
        }

        return $data;
    }

    // relationship start

    public function nodes()
    {
        return $this->hasMany(Node::class, 'workflow_id', 'id');
    }

    // relationship end
}
