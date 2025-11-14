<?php

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
    /**
     * The attributes that aren't mass assignable.
     * 默认设置为不能覆写的字段 系统自动生成
     *
     * @var array<string>|bool
     */
    protected $guarded = ['sys_admin_uuid', 'id'];

    /**
     * 模型存在多语言的字段
     *
     * @var array
     */
    public $locale_columns = [];

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function getParseColumns()
    {
        return [];
    }
}
