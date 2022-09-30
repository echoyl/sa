<?php
namespace Echoyl\Sa\Models\wechat\miniprogram;

use Echoyl\Sa\Models\wechat\offiaccount\User as OffiaccountUser;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_miniprogram_user';

    public function account()
    {
        return $this->hasOne(Account::class,'id','account_id')->select(['id','appname'])->withDefault(['appname'=>'-','id'=>0]);
    }
    
    public function offiaccountUser()
    {
        return $this->hasOne(OffiaccountUser::class,'unionid','unionid');
    }

}