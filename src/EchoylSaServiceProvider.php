<?php

namespace Echoyl\Sa;

use Illuminate\Support\ServiceProvider;

class EchoylSaServiceProvider extends ServiceProvider
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

        $router = $this->app['router'];
 
        if (method_exists($router, 'aliasMiddleware')) {
            return $router->aliasMiddleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);
        }
    
        return $router->middleware('echoyl.sa', \Echoyl\Sa\Http\Middleware\AdminAuth::class);

    }
}
