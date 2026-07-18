<?php

namespace Echoyl\Sa\Traits;

use Echoyl\Sa\Services\HelperService;

trait WithPlatformId
{
    public $with_platform_id = true;

    /**
     * 设置admin_id字段数据
     *
     * @param [需要插入的数据] $data
     * @return void
     */
    public function getPlatformIdData($data)
    {
        $platform_service = HelperService::getPlatformService();
        if (! $platform_service) {
            return $data;
        }
        $key = $platform_service::$platform_key;

        $admin_platform_id = $platform_service::getUserPlatformId();
        if ($admin_platform_id && ! isset($data[$key])) {
            // 只要用户存在平台id，则添加到数据中
            $data[$key] = $admin_platform_id;
        }

        return $data;
    }
}
