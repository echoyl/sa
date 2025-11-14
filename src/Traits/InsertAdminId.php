<?php

namespace Echoyl\Sa\Traits;

use Echoyl\Sa\Services\AdminService;

trait InsertAdminId
{
    /**
     * admin id name
     *
     * @var string
     */
    protected $admin_id_name = 'sys_admin_id';

    public $with_system_admin_id = true;

    /**
     * 设置admin_id字段数据
     *
     * @param [需要插入的数据] $data
     * @return void
     */
    public function getSysAdminIdData($data)
    {
        $admin = AdminService::user();
        if ($admin && ! isset($data[$this->admin_id_name])) {
            $data[$this->admin_id_name] = $admin['id'];
        }

        return $data;
    }
}
