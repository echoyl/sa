<?php

namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\Base;

// customer namespace start

// customer namespace end
class Menu extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_menu';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'open',
                    'type' => 'switch',
                    'default' => 0,
                    'with' => true,
                    'data' => [
                        [
                            'label' => '未启用',
                            'value' => 0,
                        ],
                        [
                            'label' => '启用',
                            'value' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'wechat_offiaccount_id',
                    'type' => 'select',
                    'default' => 0,
                    'data' => '[]',
                    'with' => true,
                ],
                [
                    'name' => 'content',
                    'type' => 'json',
                    'default' => '',
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
