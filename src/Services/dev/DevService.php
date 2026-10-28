<?php

namespace Echoyl\Sa\Services\dev;

use Echoyl\Sa\Services\dev\Concerns\BuildsDevTrees;
use Echoyl\Sa\Services\dev\Concerns\GeneratesControllerFiles;
use Echoyl\Sa\Services\dev\Concerns\GeneratesModelFiles;
use Echoyl\Sa\Services\dev\Concerns\HasDevData;
use Echoyl\Sa\Services\dev\Concerns\RegistersDevRoutes;

/**
 * 开发工具核心服务
 *
 * 具体职责按 trait 拆分到 Concerns 目录：
 * - HasDevData             菜单/模型数据读取、公共工具方法
 * - GeneratesModelFiles    模型文件、数据表 schema 代码生成
 * - GeneratesControllerFiles 控制器文件代码生成
 * - BuildsDevTrees         菜单/模型树、表格&表单配置转换
 * - RegistersDevRoutes     后台动态路由生成与缓存
 */
class DevService
{
    use HasDevData;
    use GeneratesModelFiles;
    use GeneratesControllerFiles;
    use BuildsDevTrees;
    use RegistersDevRoutes;

    /**
     * 生成代码过程中的提示信息，配合 line() 使用
     *
     * @var array
     */
    public $msg = [];

    /**
     * 代码模板目录
     *
     * @var string
     */
    public $tpl_path = __DIR__.'/tpl/';

    /**
     * 动态路由定义缓存版本号，路由生成逻辑变更时 +1 即可让旧缓存文件失效
     */
    public const ROUTE_CACHE_VERSION = 1;

    /**
     * 动态路由定义缓存文件（存放在 bootstrap/cache 目录）
     */
    public const ROUTE_CACHE_FILE = 'sa-routes.php';

    /**
     * 动态路由缓存代次号 key（存放在共享 Cache 中，菜单/模型变更时 +1 使所有缓存文件失效）
     */
    public const ROUTE_CACHE_GENERATION_KEY = 'sa_route_cache_generation';
}
