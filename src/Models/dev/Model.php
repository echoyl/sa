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

    public function getParseColumns()
    {
        static $data = [];
        if(empty($data))
        {
            $data = [
                ['name' => 'parent_id', 'type' => '', 'default' => 0],
                ["name" => "admin_type", "type" => "select", "default" => env('APP_NAME'), "data" => [
                    ["label" => "项目", "value" => env('APP_NAME')],
                    ["label" => "系统", "value" => 'system'],
                ], "with" => true],
                ['name'=>'columns','type'=>'json','default'=>''],
                ['name'=>'search_columns','type'=>'json','default'=>'{}'],
                ['name'=>'unique_fields','type'=>'json','default'=>''],
                ['name'=>'setting','type'=>'json','default'=>''],
                //['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
            ];
        }
        return $data;
    }

    public function getChild($cid = 0, $whereIn = [],$parseData = false,$max_level = 0,$level = 1,$displayorder = [['type','asc'],['id','asc']])
    {
        $list = $this->where(['parent_id' => $cid])->whereIn('admin_type',$whereIn);
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

    public function relations()
    {
        return $this->hasMany(Relation::class,'model_id','id');
    }

    public function menu()
    {
        return $this->hasOne(Menu::class,'admin_model_id','id');
    }


}
