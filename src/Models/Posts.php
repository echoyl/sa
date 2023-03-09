<?php

namespace Echoyl\Sa\Models;

class Posts extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'posts';

    public function category()
    {
        return $this->hasOne(Category::class,'id','category_id');
    }
}