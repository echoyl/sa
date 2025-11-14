<?php

namespace Echoyl\Sa\Models\locale;

use Echoyl\Sa\Models\Category as SaCategory;

// customer namespace start

// customer namespace end

class Config extends SaCategory
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'locale_config';

    /**
     * 在模型表中的id
     *
     * @var int
     */
    public $model_id = 404;

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'category',
                    'type' => 'model',
                    'class' => Category::class,
                    'foreign_key' => 'id',
                ],
                [
                    'name' => 'state',
                    'type' => 'switch',
                    'default' => 1,
                    'with' => true,
                    'data' => [
                        [
                            'label' => '禁用',
                            'value' => 0,
                        ],
                        [
                            'label' => '启用',
                            'value' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'category_id',
                    'type' => 'select',
                    'default' => 0,
                    'class' => Category::class,
                    'columns' => [
                        'title as label',
                        'id as value',
                        'title',
                        'id',
                    ],
                    'no_category' => true,
                    'with' => true,
                    'table_menu' => true,
                ],
            ];
        }

        return $data;
    }

    // relationship start

    public function category()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    // relationship end

    // customer code start

    // customer code end

}
