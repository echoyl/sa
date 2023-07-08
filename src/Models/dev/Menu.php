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

    public function getChild($cid = 0, $whereIn = [],$parseData = false,$max_level = 0,$level = 1,$displayorder = [['displayorder','desc'],['id','asc']])
    {
        $list = $this->where(['parent_id' => $cid])->whereIn('type',$whereIn);
        foreach($displayorder as $dis)
        {
            $list = $list->orderBy($dis[0],$dis[1]);
        }
        $list = $list->get()->toArray();
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

    public function adminModel()
    {
        return $this->hasOne(Model::class,'id','admin_model_id');
    }

}
