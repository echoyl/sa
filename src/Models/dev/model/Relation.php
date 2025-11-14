<?php

namespace Echoyl\Sa\Models\dev\model;

use Echoyl\Sa\Models\Base;
use Echoyl\Sa\Models\dev\Model;

class Relation extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'dev_model_relation';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                ['name' => 'search_columns', 'type' => 'selects', 'default' => ''],
                ['name' => 'with_sum', 'type' => 'selects', 'default' => ''],
                ['name' => 'select_columns', 'type' => 'selects', 'default' => ''],
                ['name' => 'in_page_select_columns', 'type' => 'selects', 'default' => ''],
                ['name' => 'with_default', 'type' => 'json', 'default' => ''],
                ['name' => 'filter', 'type' => 'json', 'default' => ''],
                ['name' => 'order_by', 'type' => 'json', 'default' => ''],
                ['name' => 'setting', 'type' => 'json', 'default' => ''],
            ];
        }

        return $data;
    }

    public function model()
    {
        return $this->hasOne(Model::class, 'id', 'model_id');
    }

    public function foreignModel()
    {
        return $this->hasOne(Model::class, 'id', 'foreign_model_id');
    }
}
