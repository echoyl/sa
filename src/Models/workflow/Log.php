<?php
namespace Echoyl\Sa\Models\workflow;
use Echoyl\Sa\Models\workflow\Node;
use Echoyl\Sa\Models\Base;

class Log extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'workflow_log';

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			    [
			        'name' => 'node',
			        'type' => 'model',
			        'class' => Node::class,
			    ],
			    [
			        'name' => 'state',
			        'type' => 'switch',
			        'default' => 0,
			    ],
			];
        }
        return $data;
    }

    //relationship start
    
    public function node()
    {
        return $this->hasOne(Node::class,'id','node_id');
    }
    
    //relationship end
}