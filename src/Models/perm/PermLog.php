<?php

namespace Echoyl\Sa\Models\perm;
use Illuminate\Database\Eloquent\Model;

class PermLog extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_log';

    public function user()
    {
        return $this->hasOne(PermUser::class,'id','user_id');
    }
}