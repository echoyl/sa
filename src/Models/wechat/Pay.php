<?php
namespace Echoyl\Sa\Models\wechat;
use Illuminate\Database\Eloquent\Model;

class Pay extends Model
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