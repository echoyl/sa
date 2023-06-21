<?php
namespace Echoyl\Sa\Models\menu;

use Echoyl\Sa\Models\Category;

class Menu extends Category
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'menu';

    public function category()
    {
        return $this->hasOne(Category::class,'id','category_id')->withDefault(['id'=>0,'title'=>'']);
    }

    public function content()
    {
        return $this->hasOne(Posts::class,'id','content_id')->withDefault(['id'=>0,'title'=>'']);
    }

}
