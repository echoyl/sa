<?php

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_role';

    public function format($id = 0)
    {
        $data = $this->orderBy('id','asc')->get();
        $ret = [];
        foreach($data as $val)
        {
            $ret[] = [
                'id'=>$val['id'],'name'=>$val['rolename'],'children'=>[]
            ];
        }
        return $ret;
    }

}