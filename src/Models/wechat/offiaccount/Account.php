<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\Base;

class Account extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_account';

    
    // public function wxapp()
    // {
    //     return $this->hasOne(Wxapp::class,'unionid','unionid');
    // }

}