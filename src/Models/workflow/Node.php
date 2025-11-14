<?php

namespace Echoyl\Sa\Models\workflow;

use Echoyl\Sa\Models\Base;
use Echoyl\Sa\Models\workflow\Node as WorkflowNode;

class Node extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'workflow_node';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'upstream',
                    'type' => 'model',
                    'class' => WorkflowNode::class,
                ],
                [
                    'name' => 'downstream',
                    'type' => 'model',
                    'class' => WorkflowNode::class,
                ],
                [
                    'name' => 'config',
                    'type' => 'json',
                    'default' => '',
                ],
                [
                    'name' => 'upstream_id',
                    'type' => 'select',
                    'default' => 0,
                    'data' => (new WorkflowNode)->get()->toArray(),
                    'with' => true,
                ],
                [
                    'name' => 'downstream_id',
                    'type' => 'select',
                    'default' => 0,
                    'data' => (new WorkflowNode)->get()->toArray(),
                    'with' => true,
                ],
            ];
        }

        return $data;
    }

    // relationship start

    public function upstream()
    {
        return $this->hasOne(Node::class, 'id', 'upstream_id');
    }

    public function downstream()
    {
        return $this->hasOne(Node::class, 'id', 'downstream_id');
    }

    // relationship end
}
