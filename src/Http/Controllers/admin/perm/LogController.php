<?php

namespace Echoyl\Sa\Http\Controllers\admin\perm;

use Echoyl\Sa\Http\Controllers\admin\CrudController;
use Echoyl\Sa\Models\perm\Log;

// customer namespace start

// customer namespace end

/**
 * @property \App\Services\echoyl\AdminAppService $service
 */
class LogController extends CrudController
{
    // customer property start

    // customer property end
    public function __construct()
    {
        parent::__construct();
        $this->with_column = [
            'user' => function ($q0) {
                $q0->select(['id', 'username']);
            },
        ];
        $this->search_config = [
            [
                'name' => 'created_at',
                'columns' => [
                    'created_at',
                ],
                'where_type' => 'whereBetween',
            ],
            [
                'name' => 'keyword',
                'columns' => [
                    'type',
                ],
                'where_type' => 'like',
            ],
        ];
        $this->model = new Log;
        $this->model_class = Log::class;
        // customer construct start

        // customer construct end
    }

    // customer code start
    public function beforePost($data, $id = 0)
    {
        return $this->fail([1, '操作日记不能更新']);
    }

    public function beforeDestroy($m)
    {
        return $m->where([['id', '=', 0]]);
    }
    // customer code end

}
