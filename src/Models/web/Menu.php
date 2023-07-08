<?php
namespace Echoyl\Sa\Models\web;

use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\dev\Model;

class Menu extends Category
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'menu';

    // public function category()
    // {
    //     return $this->hasOne(Category::class,'id','category_id')->withDefault(['id'=>0,'title'=>'']);
    // }

    // public function content()
    // {
    //     return $this->hasOne(Posts::class,'id','content_id')->withDefault(['id'=>0,'title'=>'']);
    // }

    public function adminModel()
    {
        return $this->hasOne(Model::class,'id','admin_model_id');
    }

}
