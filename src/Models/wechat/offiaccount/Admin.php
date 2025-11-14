<?php

namespace Echoyl\Sa\Models\wechat\offiaccount;

use Echoyl\Sa\Models\Base;

// customer namespace start

// customer namespace end

class Admin extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_offiaccount_admin';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'user',
                    'type' => 'model',
                    'class' => User::class,
                    'foreign_key' => 'openid',
                ],
                [
                    'name' => 'openid',
                    'type' => 'search_select',
                    'default' => 0,
                    'data_name' => 'user',
                    'label' => 'nickname',
                    'value' => 'openid',
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

    public function user()
    {
        return $this->hasOne(User::class, 'openid', 'openid');
    }

    // relationship end

    // customer code start

    // customer code end

}
