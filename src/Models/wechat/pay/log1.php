<?php
namespace Echoyl\Sa\Models\wechat\pay;

use Echoyl\Sa\Models\wechat\miniprogram\User as MiniprogramUser;
use Echoyl\Sa\Models\wechat\offiaccount\User;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_pay_log';

    
    public function offiaccountUser()
    {
        return $this->hasOne(User::class,'openid','offiaccount_user_openid');
    }

    public function minprogramUser()
    {
        return $this->hasOne(MiniprogramUser::class,'openid','miniprogram_user_openid');
    }

}