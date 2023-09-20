<?php
namespace Echoyl\Sa\Models\wechat\miniprogram\user;

use Echoyl\Sa\Models\Base;

class Bind extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_miniprogram_user_bind';

    public $timestamps = false;
    
    //relationship end
}