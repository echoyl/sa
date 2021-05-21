<?php
namespace Echoyl\Sa\Models\wechat;
use Illuminate\Database\Eloquent\Model;

class Wxapp extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_wxapp';

    public function wx()
    {
        return $this->hasOne(Wx::class,'unionid','unionid');
    }
}