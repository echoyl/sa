<?php

namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\Base;

class Account extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_account';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'state',
                    'type' => 'switch',
                    'default' => 0,
                    'table_menu' => true,
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
                    'name' => 'qrcode',
                    'type' => 'image',
                    'default' => '',
                ],
            ];
        }

        return $data;
    }

    // relationship start
    public function menus()
    {
        return $this->hasMany(Menu::class, 'wechat_offiaccount_id', 'id');
    }

    // relationship end
}
