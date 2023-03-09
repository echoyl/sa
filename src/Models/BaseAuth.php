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
    
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }
    
}