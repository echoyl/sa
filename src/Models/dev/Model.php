<?php

namespace Echoyl\Sa\Models\dev;

use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Models\dev\model\Relation;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Utils;

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
        if (empty($data)) {
            $data = [
                ['name' => 'parent_id', 'type' => '', 'default' => 0],
                ['name' => 'admin_type', 'type' => 'select', 'default' => DevService::appname(), 'data' => Utils::packageTypes(), 'with' => true],
                ['name' => 'columns', 'type' => 'json', 'default' => ''],
                ['name' => 'search_columns', 'type' => 'json', 'default' => ''],
                ['name' => 'unique_fields', 'type' => 'json', 'default' => ''],
                ['name' => 'setting', 'type' => 'json', 'default' => ''],
                // ['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
            ];
        }

        return $data;
    }

    public function relations()
    {
        return $this->hasMany(Relation::class, 'model_id', 'id');
    }

    public function menu()
    {
        return $this->hasOne(Menu::class, 'admin_model_id', 'id');
    }
}
