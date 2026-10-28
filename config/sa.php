<?php

use App\Services\deadmin\AdminAppService;
use App\Services\deadmin\AppApiService;
use Echoyl\Sa\Models\perm\User;
use Echoyl\Sa\Models\User as ModelsUser;

return [
    // 路由前缀（用于路由分组和 URL 拼接），放在配置中以保证 config:cache 后依然有效
    'app_prefix' => env('APP_PREFIX', ''),
    'app_admin_prefix' => env('APP_ADMIN_PREFIX', 'sadmin'),
    'app_locale' => env('APP_LOCALE', 'zh-CN'), // 默认语言，en 会自动转换为 en-US
    'max_page_size' => 1000, // 列表分页每页最大条数，防止 pageSize 传入过大导致慢查询/内存暴涨
    'route_cache' => env('SA_ROUTE_CACHE', true), // 后台动态菜单路由定义缓存到 bootstrap/cache/sa-routes.php，菜单变更后执行 php artisan sa:route cache
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
    | 阿里云配置
    |--------------------------------------------------------------------------
    |
    | 放在配置中以保证 config:cache 后依然有效
    |
    */

    'aliyun_oss' => env('ALIYUN_OSS'), // OSS bucket 名称，为空则使用本地存储
    'aliyun_domain' => env('ALIYUN_DOMAIN'), // OSS 访问域名
    'ali_access_key_id' => env('ALI_accessKeyId'),
    'ali_access_key_secret' => env('ALI_accessKeySecret'),
    'ali_access_key_id_video' => env('ALI_accessKeyId_video'),
    'ali_access_key_secret_video' => env('ALI_accessKeySecret_video'),

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
    | 多语言配置
    |--------------------------------------------------------------------------
    |
    | 未设置则使用默认值
    |
    */
    'locales' => [
        ['title' => '简体中文', 'name' => 'zh-CN'],
        ['title' => 'English', 'name' => 'en-US'],
        // ['title' => '繁體中文', 'name' => 'zh-TW'],
    ],

    /*
    |--------------------------------------------------------------------------
    | PlatformService 配置
    |--------------------------------------------------------------------------
    |
    | 默认使用包中的service
    |
    */
    //'platformService' => PlatformService::class, // 平台service 配置后会开启平台功能
    //'platformClass' => Enterprise::class, // 平台模型
];
