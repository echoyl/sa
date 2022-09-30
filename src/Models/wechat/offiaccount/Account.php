<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;
use Illuminate\Database\Eloquent\Model;

class Account extends Model
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