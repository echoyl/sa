<?php

namespace Echoyl\Sa\Models;

use Illuminate\Database\Eloquent\Model;

class Pca extends Model
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'pca';

    public function children()
    {
        return $this->hasMany(self::class, 'pcode', 'code');
    }

}
