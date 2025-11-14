<?php

namespace Echoyl\Sa\Http\Controllers\admin\wechat\offiaccount;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\wechat\offiaccount\Admin;
use Echoyl\Sa\Services\AdminService;

// customer namespace start

// customer namespace end

/**
 * @property \App\Services\echoyl\AdminAppService $service
 */
class AdminController extends CrudController
{
    // customer property start

    // customer property end
    public function __construct()
    {
        parent::__construct();
        $this->with_column = [
            'user',
        ];
        $this->uniqueFields = [
            [
                'columns' => [
                    'openid',
                    'user_id',
                ],
                'message' => '该记录已存在',
            ],
        ];
        $this->model = new Admin;
        // customer construct start

        // customer construct end
    }

    // customer code start
    public function listData(&$list)
    {
        $model = AdminService::getUserModel();
        foreach ($list as $key => $val) {
            $val['admin'] = $model->select(['username', 'id'])->where(['id' => $val['user_id']])->first();
            $list[$key] = $val;
        }
    }

    public function beforePost(&$data, $id, $item)
    {
        if ($this->is_post) {
            if (isset($data['user_id'])) {
                $data['user_id'] = $data['user_id']['id'];
            }
            // 检测是否有数据了
        }
    }

    public function postData(&$item)
    {
        if (isset($item['user_id'])) {
            // 获取用户
            $model = AdminService::getUserModel();
            $user = $model->select(['username as label', 'id as value'])->where(['id' => $item['user_id']])->first();
            if ($user) {
                $item['user_id'] = $user->toArray();
            }
        }
    }
    // customer code end

}
