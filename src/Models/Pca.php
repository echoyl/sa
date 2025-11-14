<?php

namespace Echoyl\Sa\Models;

class Pca extends Category
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'pca';

    /**
     * 在模型表中的id
     *
     * @var int
     */
    public $model_id = 25;

    public $timestamps = false;

    public function children($pid = 0)
    {
        return $this->hasMany(self::class, 'pcode', 'code');
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'code', 'pcode');
    }

    public function childrenIds($id, $self = true)
    {
        // 获取子类的所有id
        $ids = [];
        if (! $id) {
            return $ids;
        }
        if ($self) {
            $ids[] = $id;
        }

        $children = self::allData($this->table)->filter(function ($user) use ($id) {
            return $user->pcode == $id;
        });
        if ($children) {
            foreach ($children as $val) {
                $ids[] = $val['code'];
                $ids = array_merge($ids, $this->childrenIds($val['code'], $self));
            }
        }

        return array_filter(array_unique($ids));
    }
}
