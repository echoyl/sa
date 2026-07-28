<?php

use Echoyl\Sa\Exceptions\AException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(fn () => null);
        $middleware->alias([]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (AException $e) {
            //
        });
        $exceptions->render(function (Throwable $e) {
            if (request()->ajax() || request()->wantsJson() || request()->isJson()) {
                return response()->json([
                    'msg' => $e->getMessage(),
                    'code' => match (true) {
                        $e instanceof AException => $e->getCode(),
                        $e instanceof AuthenticationException => 1001,
                        default => 1,
                    },
                    'data' => [],
                ], 200);
            }
        });
    })->create();
