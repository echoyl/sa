<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\wechat\miniprogram\User as MiniprogramUser;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_user';

    public function account()
    {
        return $this->hasOne(Account::class,'id','account_id')->select(['id','appname'])->withDefault(['appname'=>'-','id'=>0]);
    }

    
    
    public function miniprogramUser()
    {
        return $this->hasOne(MiniprogramUser::class,'unionid','unionid');
    }

}