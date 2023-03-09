<?php
namespace Echoyl\Sa\Models\wechat;

use Echoyl\Sa\Models\Base;

class Pay extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_pay';

    
    // public function wxapp()
    // {
    //     return $this->hasOne(Wxapp::class,'unionid','unionid');
    // }

}