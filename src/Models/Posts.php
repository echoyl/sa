<?php

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Posts extends Model
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

    public function silbings($news)
    {
        //前一个
        $prev = self::where([
            'status'=>1,['id','>',$news['id']],['displayorder','=',$news['displayorder']],['category_id','=',$news['category_id']]
        ])->orWhere([
            ['displayorder','>',$news['displayorder']],['id','!=',$news['id']],['category_id','=',$news['category_id']]
        ])->orderBy('displayorder','asc')->orderBy('id','asc')->first();

        //后一个
        $next = self::where([
            'status'=>1,['id','<',$news['id']],['displayorder','=',$news['displayorder']],['category_id','=',$news['category_id']]
        ])->orWhere([
            ['displayorder','<',$news['displayorder']],['id','!=',$news['id']],['category_id','=',$news['category_id']]
        ])->orderBy('displayorder','desc')->orderBy('id','desc')->first();
        return ['prev'=>$prev,'next'=>$next];

    }

}