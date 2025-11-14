<?php

namespace Echoyl\Sa\Models\web;

use Echoyl\Sa\Models\Category as SaCategory;
// customer namespace start
use Echoyl\Sa\Models\dev\Model;

// customer namespace end

class Menu extends SaCategory
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'web_menu';

    /**
     * 在模型表中的id
     *
     * @var int
     */
    public $model_id = 110;

    /**
     * 模型存在多语言的字段
     *
     * @var array
     */
    public $locale_columns = ['title'];

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'banner',
                    'type' => 'image',
                    'default' => '',
                ],
                [
                    'name' => 'blank',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'bottom',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'category_all',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'category_default_first',
                    'type' => 'switch',
                    'default' => 1,
                ],
                [
                    'name' => 'category_id',
                    'type' => 'cascader',
                    'default' => '',
                ],
                [
                    'name' => 'category_show_bottom',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'category_show_top',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'content_detail',
                    'type' => 'tinyEditor',
                    'default' => '',
                ],
                [
                    'name' => 'hidden',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'index_show',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'list_show',
                    'type' => 'switch',
                    'default' => 0,
                ],
                [
                    'name' => 'pics',
                    'type' => 'image',
                    'default' => '',
                ],
                [
                    'name' => 'specs',
                    'type' => 'config',
                    'default' => '',
                ],
                [
                    'name' => 'state',
                    'type' => 'switch',
                    'default' => 1,
                    'table_menu' => true,
                    'with' => true,
                    'data' => [
                        [
                            'label' => '关闭',
                            'value' => 0,
                        ],
                        [
                            'label' => '开启',
                            'value' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'titlepic',
                    'type' => 'image',
                    'default' => '',
                ],
                [
                    'name' => 'top',
                    'type' => 'switch',
                    'default' => 0,
                ],
            ];
        }

        return $data;
    }

    // relationship start

    // relationship end

    // customer code start
    public function adminModel()
    {
        return $this->hasOne(Model::class, 'id', 'admin_model_id');
    }
    // customer code end

}
