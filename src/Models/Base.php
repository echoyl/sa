<?php

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    
    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function getParseColumns()
    {
        return [];
    }
    
}