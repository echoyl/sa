<?php
namespace Echoyl\Sa\Models\wechat\miniprogram;

use Echoyl\Sa\Models\Base;

class Account extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_miniprogram_account';

    
    // public function wxapp()
    // {
    //     return $this->hasOne(Wxapp::class,'unionid','unionid');
    // }

}