<?php

namespace Echoyl\Sa\Models\locale;

use Echoyl\Sa\Models\Category as SaCategory;

// customer namespace start

// customer namespace end

class Category extends SaCategory
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'locale_category';

    /**
     * 在模型表中的id
     *
     * @var int
     */
    public $model_id = 403;

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'titlepic',
                    'type' => 'image',
                    'default' => '',
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
            ];
        }

        return $data;
    }

    // relationship start

    // relationship end

    // customer code start

    // customer code end

}
