<?php

namespace Echoyl\Sa\Models\perm;

use Echoyl\Sa\Models\BaseAuth;
use Laravel\Sanctum\PersonalAccessToken;

class User extends BaseAuth
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'perm_user';

    public function role()
    {
        return $this->hasOne(Role::class,'id','roleid');
    }


    public function logs()
    {
        return $this->hasMany(PersonalAccessToken::class,'tokenable_id','id')->where(['tokenable_type'=>'Echoyl\Sa\Models\perm\User']);
    }
}