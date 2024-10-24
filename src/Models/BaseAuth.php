<?php

namespace Echoyl\Sa\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class BaseAuth extends Authenticatable
{
    use HasApiTokens,Notifiable;
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    
    /**
     * The attributes that aren't mass assignable.
     * 默认设置为不能覆写的字段 系统自动生成
     *
     * @var array<string>|bool
     */
    protected $guarded = ['sys_admin_uuid','id']; 

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function getParseColumns()
    {
        return [];
    }
    
}