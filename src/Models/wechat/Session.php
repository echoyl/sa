<?php

namespace Echoyl\Sa\Models\wechat;

use Echoyl\Sa\Models\Base;

class Session extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_session';
    public $timestamps = false;
}