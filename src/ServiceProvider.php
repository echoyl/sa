<?php

namespace Echoyl\Sa;

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
        $this->app->bind(SaAdminAppServiceInterface::class,config('sa.adminAppService'));
        $this->app->bind(SaServiceInterface::class,config('sa.service'));
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SaCommand::class
            ]);
        }

        $router = $this->app['router'];
 
        if (method_exists($router, 'aliasMiddleware')) {
            $router->aliasMiddleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);
            $router->aliasMiddleware('echoyl.remember', \Echoyl\Sa\Http\Middleware\RememberToken::class);
            $router->aliasMiddleware('echoyl.permcheck', \Echoyl\Sa\Http\Middleware\PermCheck::class);
        }
        $router->middleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);
        $router->middleware('echoyl.remember', \Echoyl\Sa\Http\Middleware\RememberToken::class);
        $router->middleware('echoyl.permcheck', \Echoyl\Sa\Http\Middleware\PermCheck::class);

    }
}
