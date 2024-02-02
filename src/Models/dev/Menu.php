<?php

namespace Echoyl\Sa\Models\dev;

use Echoyl\Sa\Models\Category;

class Menu extends Category
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'dev_menu';

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }


    public function adminModel()
    {
        return $this->hasOne(Model::class,'id','admin_model_id');
    }

}
