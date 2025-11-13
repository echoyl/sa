<?php

namespace Echoyl\Sa;

use Echoyl\Sa\Console\Commands\HelperCommand;
use Echoyl\Sa\Console\Commands\SaCommand;
use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;
use Echoyl\Sa\Constracts\SaServiceInterface;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->bind(SaAdminAppServiceInterface::class, config('sa.adminAppService'));
        $this->app->bind(SaServiceInterface::class, config('sa.service'));
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->loadRoutesFrom(__DIR__.'/routes.php');

        // 数据迁移
        // $this->loadMigrationsFrom(__DIR__.'/../database/schema');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SaCommand::class,
                HelperCommand::class,
            ]);
            // 静态发布文件
            // build的文件
            $this->publishes([__DIR__.'/../static/dist' => public_path('antadmin')], 'antadmin');
            // 前端开发文件
            // $this->publishes([__DIR__ . '/../static/dev' => public_path('antadmindev')], 'antadmindev');
            // 配置文件
            $this->publishes([
                __DIR__.'/../config/sa.php' => config_path('sa.php'),
            ], 'deadmin');
            // 数据库基础文件
            $this->publishes([
                __DIR__.'/../database/schema/mysql-schema.dump' => database_path('schema/mysql-schema.dump'),
            ], 'deadmin');

        }

        $router = $this->app['router'];

        if (method_exists($router, 'aliasMiddleware')) {
            $router->aliasMiddleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);
            $router->aliasMiddleware('echoyl.remember', \Echoyl\Sa\Http\Middleware\RememberToken::class);
            $router->aliasMiddleware('echoyl.permcheck', \Echoyl\Sa\Http\Middleware\PermCheck::class);
            $router->aliasMiddleware('echoyl.superadmin', \Echoyl\Sa\Http\Middleware\SuperAdminAuth::class);
        }
        $router->middleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);
        $router->middleware('echoyl.remember', \Echoyl\Sa\Http\Middleware\RememberToken::class);
        $router->middleware('echoyl.permcheck', \Echoyl\Sa\Http\Middleware\PermCheck::class);
        $router->middleware('echoyl.superadmin', \Echoyl\Sa\Http\Middleware\SuperAdminAuth::class);
    }
}
