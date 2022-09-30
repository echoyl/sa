<?php
namespace Echoyl\Sa\Models\wechat\miniprogram;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
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