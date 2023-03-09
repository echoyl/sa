<?php
namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\BaseAuth;
use Echoyl\Sa\Models\wechat\miniprogram\User as MiniprogramUser;

class User extends BaseAuth
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