<?php

namespace Echoyl\Sa\Models\perm;

use Echoyl\Sa\Models\Base;

class Log extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_log';

    public function user()
    {
        return $this->hasOne(User::class,'id','user_id');
    }
}