<?php

namespace Echoyl\Sa;

use Echoyl\Sa\Console\Commands\HelperCommand;
use Echoyl\Sa\Console\Commands\SaCommand;
use Echoyl\Sa\Constracts\SaAdminAppServiceInterface;
use Echoyl\Sa\Constracts\SaServiceInterface;
use Echoyl\Sa\Http\Middleware\AdminAuth;
use Echoyl\Sa\Http\Middleware\PermCheck;
use Echoyl\Sa\Http\Middleware\RememberToken;
use Echoyl\Sa\Http\Middleware\SuperAdminAuth;
use Illuminate\Contracts\Http\Kernel;
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

        // 确保在应用 boot 完成后再 prepend 中间件到路由组（兼容没有 Kernel::prependMiddleware 的版本）
        $this->app->booted(function () {
            $router = $this->app['router'];
            if (method_exists($router, 'prependMiddlewareToGroup')) {
                $router->prependMiddlewareToGroup('api', RememberToken::class);
            } else {
                // 回退到注册别名（确保中间件可用）
                if (method_exists($router, 'aliasMiddleware')) {
                    $router->aliasMiddleware('echoyl.remember', RememberToken::class);
                }
            }
        });

        // 加载 namespaced PHP 翻译 (使用 trans('sa::messages.key'))
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'sa');

        // 加载 JSON 翻译 (使用 __('key'))
        $this->loadJsonTranslationsFrom(__DIR__.'/../resources/lang');

        // 数据迁移
        // $this->loadMigrationsFrom(__DIR__.'/../database/schema');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SaCommand::class,
                HelperCommand::class,
            ]);
            // 静态发布文件
            // build的文件
            $this->publishes([__DIR__.'/../static/antadmin' => public_path('antadmin')], 'antadmin');
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
            // Controllers
            $this->publishes([
                __DIR__.'/../static/deadmin/Controllers' => app_path('Http/Controllers/admin'),
            ], 'deadmin');
            // Services
            $this->publishes([
                __DIR__.'/../static/deadmin/Services' => app_path('Services/deadmin'),
            ], 'deadmin');
            // app.php
            $this->publishes([
                __DIR__.'/../static/deadmin/app.php' => base_path('bootstrap/app.php'),
            ], 'deadmin');
        }

        $router = $this->app['router'];

        if (method_exists($router, 'aliasMiddleware')) {
            $router->aliasMiddleware('echoyl.sa', AdminAuth::class);
            $router->aliasMiddleware('echoyl.remember', RememberToken::class);
            $router->aliasMiddleware('echoyl.permcheck', PermCheck::class);
            $router->aliasMiddleware('echoyl.superadmin', SuperAdminAuth::class);
        }
        $router->middleware('echoyl.sa', AdminAuth::class);
        $router->middleware('echoyl.remember', RememberToken::class);
        $router->middleware('echoyl.permcheck', PermCheck::class);
        $router->middleware('echoyl.superadmin', SuperAdminAuth::class);
    }
}
