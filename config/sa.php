<?php

use App\Services\deadmin\AdminAppService;
use App\Services\deadmin\AppApiService;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Models\User as ModelsUser;

return [
    'service' => AppApiService::class,
    'imageSize' => [
        's' => [
            'w' => 150,
            'h' => 150,
        ],
        'm' => [
            'w' => 300,
            'h' => 300,
        ],
    ],
    'userModel' => User::class, // 后台用户使用数据模型
    'frontUserModel' => ModelsUser::class,
    'adminAppService' => AdminAppService::class, // 后台通用service
    'admin_upload_max_wh' => 1000, // 后台上传图片最大的宽高，超过该值后会压缩至该宽高
    'user_upload_max_wh' => 1200, // 前台上传图片最大的宽高，超过该值后会压缩至该宽高
    'upload_tmp_enable' => false, // 上传是否开启tmp，开启后上传数据都会存在tmp中保存数据后才会移动文件（tmp文件过期后删除）

    /*
    |--------------------------------------------------------------------------
    | 格式化代码配置
    |--------------------------------------------------------------------------
    |
    | 是否开启生成代码格式化
    |
    */

    'formatCode' => [
        'enable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 自定义返回的信息 参考 vendor\echoyl\sa\src\Helpers\ResponseEnum.php
    |--------------------------------------------------------------------------
    |
    | 未设置则使用默认值
    |
    */
    'responseEnum' => [
        'CLIENT_HTTP_UNAUTHORIZED_PERM' => [401301, '账号无权操作'],
    ],
];
