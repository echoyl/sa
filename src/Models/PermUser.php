<?php

namespace Echoyl\Sa\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class PermUser extends Authenticatable
{
    use HasApiTokens,Notifiable;
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

}