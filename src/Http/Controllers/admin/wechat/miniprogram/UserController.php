<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\miniprogram;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\miniprogram\User;
// customer namespace start
use Illuminate\Support\Arr;

// customer namespace end

class UserController extends CrudController
{
    public $with_column = ['app'];

    public $search_config = [
        ['name' => 'appid', 'columns' => ['appid'], 'where_type' => '='],
        ['name' => 'created_at', 'columns' => ['last_used_at'], 'where_type' => 'whereBetween'],
        [
            'name' => 'keyword',
            'columns' => [
                'nickname',
                'openid',
                'mobile',
            ],
            'where_type' => 'like',
        ],
    ];

    public function __construct()
    {
        $this->model = new User;

        $this->parse_columns = [];

    }

    // customer code start
    public function listItem($item)
    {
        $avatar = Arr::get($item, 'avatar.0.url', '');
        if (! $avatar) {
            $avatar = Arr::get($item, 'origin_data.avatar', '');
        }
        $item['avatar'] = $avatar;

        return $item;

    }
    // customer code end
}
