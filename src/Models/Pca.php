<?php
namespace Echoyl\Sa\Models;

class Pca extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'pca';
    public $timestamps = false;
    public function children()
    {
        return $this->hasMany(self::class, 'pcode', 'code');
    }

}
