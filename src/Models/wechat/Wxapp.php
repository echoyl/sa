<?php
namespace Echoyl\Sa\Models\wechat;

use Echoyl\Sa\Models\Base;

class Wxapp extends Base
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