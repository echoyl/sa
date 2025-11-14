<?php

namespace Echoyl\Sa\Models\wechat\miniprogram\user;

use Echoyl\Sa\Models\Base;
use Echoyl\Sa\Models\wechat\miniprogram\User;

class Bind extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_miniprogram_user_bind';

    public $timestamps = false;

    public function user()
    {
        return $this->hasOne(User::class, 'openid', 'openid');
    }

    // relationship end
}
