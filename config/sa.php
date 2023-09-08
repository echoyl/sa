<?php

use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Models\User as ModelsUser;
use Echoyl\Sa\Services\AdminAppService;
use Echoyl\Sa\Services\AppApiService;

return [
    'service'=>AppApiService::class,
    'imageSize'=>[
        's'=>[
            'w' => 150,
            'h' => 150,
        ],
        'm'=>[
            'w' => 300,
            'h' => 300,
        ]
    ],
    'userModel'=>User::class,//后台用户使用数据模型
    'frontUserModel'=>ModelsUser::class,
    'adminAppService'=>AdminAppService::class,//后台通用service
];
