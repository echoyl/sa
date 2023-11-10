<?php

namespace Echoyl\Sa\Models\perm;

use Echoyl\Sa\Models\Base;

class Role extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_role';
    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
			    [
			        'name' => 'state',
			        'type' => 'switch',
			        'default' => 0,
			        'with' => true,
			        'data' => [
			            [
			                'label' => '禁用',
			                'value' => 0,
			            ],
			            [
			                'label' => '启用',
			                'value' => 1,
			            ],
			        ],
                    "table_menu" => true
			    ],
                [
			        'name' => 'sync_user',
			        'type' => 'switch',
			        'default' => 0,
			        'with' => true,
			        'data' => [
			            [
			                'label' => '否',
			                'value' => 0,
			            ],
			            [
			                'label' => '是',
			                'value' => 1,
			            ],
			        ],
			    ],
			];
        }
        return $data;
    }

    public function format($id = 0,$fields = ['id'=>'value','title'=>'label','children'=>'children'])
    {
        $data = $this->get()->toArray();
        $ret = [];
        foreach($data as $val)
        {
            $ret[] = [
                $fields['id']=>$val['id'],
                $fields['title']=>$val['title']
            ];
        }
        return $ret;
    }
}