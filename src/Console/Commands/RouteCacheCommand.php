<?php

namespace Echoyl\Sa\Console\Commands;

use Echoyl\Sa\Services\dev\DevService;
use Echoyl\Sa\Services\dev\MenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class RouteCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sa:route
        {action=cache : cache 生成后台动态路由缓存，clear 清理缓存}
        {--refresh : 同时刷新菜单/模型数据缓存（会重新读库）}
        {--laravel : 同时生成 Laravel 整站路由缓存（执行 route:cache）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '生成或清理后台菜单动态路由缓存（菜单/模型变更、发布代码后执行）';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $action = $this->argument('action');

        if (! in_array($action, ['cache', 'clear'], true)) {
            $this->error('action 只支持 cache 或 clear');

            return self::FAILURE;
        }

        if ($action === 'clear') {
            DevService::flushRouteCache();
            if ($this->option('laravel')) {
                $this->call('route:clear');
            }
            $this->info('后台动态路由缓存已清理');

            return self::SUCCESS;
        }

        if ($this->option('refresh')) {
            // 先让菜单/模型数据缓存失效，再用最新数据生成路由
            Cache::forget('devAllMenu');
            Cache::forget('devAllModel');
            MenuService::flushCache();
        }

        DevService::flushRouteCache();

        $start = microtime(true);
        $admin = DevService::routeDefinitions('admin');
        $system = DevService::routeDefinitions('system');
        $cache_path = DevService::routeCachePath();

        if (empty($admin) && empty($system)) {
            // 菜单表未安装/还没有菜单时不会写缓存文件，属于正常情况
            $this->warn('没有可缓存的动态路由（dev_menu 为空或未安装），跳过路由定义缓存');
        } else {
            // 用签名校验而不是 is_file：避免“删除失败遗留的旧文件”被误报为生成成功
            if (! DevService::routeCacheValid()) {
                $this->error('路由缓存文件写入失败（检查 bootstrap/cache 目录权限）：'.$cache_path);

                return self::FAILURE;
            }

            $this->info(sprintf(
                '后台动态路由缓存已生成：admin %d 条，system %d 条，耗时 %.0f ms',
                count($admin),
                count($system),
                (microtime(true) - $start) * 1000
            ));
            $this->line('缓存文件：'.$cache_path);
        }

        if ($this->option('laravel')) {
            // 注意：生成整站路由缓存后，新增/修改菜单需要重新执行本命令
            // production 环境下 route:cache 需要 --force 才会执行（ConfirmableTrait）
            $code = $this->call('route:cache', ['--force' => true]);
            if ($code !== self::SUCCESS) {
                $this->error('route:cache 执行失败（返回码 '.$code.'），请检查路由定义');

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
