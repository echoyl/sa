<?php

namespace Echoyl\Sa;

use Echoyl\Sa\Console\Commands\SaCommand;
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
            return $router->aliasMiddleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);
        }
    
        return $router->middleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);

    }
}
