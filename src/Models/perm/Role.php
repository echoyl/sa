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