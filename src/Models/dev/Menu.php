<?php

namespace Echoyl\Sa\Models\dev;

use Echoyl\Sa\Models\Category;
use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\utils\Utils;

class Menu extends Category
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'dev_menu';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $ds = new DevService;
            $data = [
                ['name' => 'category_id', 'type' => 'cascader', 'default' => ''],
                ['name' => 'admin_model', 'type' => 'model', 'default' => '', 'class' => Model::class],
                ['name' => 'admin_model_id', 'type' => 'select', 'default' => 0, 'with' => true, 'data' => $ds->getModelsTree()],
                ['name' => 'type', 'type' => 'select', 'default' => DevService::appname(), 'data' => Utils::packageTypes(), 'with' => true],
                ['name' => 'state', 'type' => 'switch', 'default' => 1, 'with' => true, 'data' => [
                    ['label' => '启用', 'value' => 1],
                    ['label' => '禁用', 'value' => 0],
                ], 'table_menu' => true],
                ['name' => 'desc', 'type' => 'json', 'default' => ''],
                ['name' => 'perms', 'type' => 'json', 'default' => ''],
                ['name' => 'icon', 'type' => 'select', 'default' => ''],
                ['name' => 'status', 'type' => 'switch', 'default' => 1, 'with' => true, 'data' => [
                    ['label' => '显示', 'value' => 1],
                    ['label' => '隐藏', 'value' => 0],
                ]],
                ['name' => 'form_config', 'type' => 'json', 'default' => ''],
                ['name' => 'other_config', 'type' => 'json', 'default' => ''],
                ['name' => 'table_config', 'type' => 'json', 'default' => ''],
                ['name' => 'setting', 'type' => 'json', 'default' => ''],
                ['name' => 'parent_id', 'type' => 'select', 'default' => 0],
            ];
        }

        return $data;
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_id');
    }

    public function adminModel()
    {
        return $this->hasOne(Model::class, 'id', 'admin_model_id');
    }
}
