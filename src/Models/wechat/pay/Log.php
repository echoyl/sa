<?php

namespace Echoyl\Sa\Models\wechat\pay;

use Echoyl\Sa\Models\Base;

// customer namespace start

// customer namespace end
class Log extends Base
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
    protected $table = 'wechat_pay_log';

    public function getParseColumns()
    {
        static $data = [];
        if (empty($data)) {
            $data = [
                [
                    'name' => 'money',
                    'type' => 'price',
                    'default' => 0,
                ],
            ];
        }

        return $data;
    }

    // relationship start

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'log_id', 'id');
    }

    // relationship end

    // customer code start

    // customer code end
}
