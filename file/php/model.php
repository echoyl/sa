<?php
namespace App\Models$namespace$;
use Illuminate\Database\Eloquent\Model;

class $name$ extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = '$table_name$';

    /*
    public function category()
    {
        return $this->hasOne(Category::class,'id','category_id')->withDefault(['id'=>0,'title'=>'']);
    }

    public function logs()
    {
        return $this->hasMany(PersonalAccessToken::class,'tokenable_id','id');
    }
    
    */
}