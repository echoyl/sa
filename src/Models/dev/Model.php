<?php
namespace Echoyl\Sa\Models\dev;

use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\dev\model\Relation;

class Model extends Category
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'dev_model';

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    public function getChild($cid = 0, $whereIn = [],$parseData = false,$max_level = 0,$level = 1)
    {
        $list = $this->where(['parent_id' => $cid])->whereIn('admin_type',$whereIn)->orderBy('displayorder', 'desc')->orderBy('type', 'asc')->orderBy('id', 'asc')->get()->toArray();
        foreach ($list as $key => $val) {
            if($parseData)
            {
                $list[$key] = $parseData($val);
            }
            if($max_level == 0 || $max_level > $level)
            {
                $children = $this->getChild($val['id'], $whereIn,$parseData,$max_level,$level+1);
                if (!empty($children)) {
                    $list[$key]['children'] = $children;
                }
            }
            
        }
        return $list;
    }

    public function relations()
    {
        return $this->hasMany(Relation::class,'model_id','id');
    }

    public function menu()
    {
        return $this->hasOne(Menu::class,'admin_model_id','id');
    }


}
