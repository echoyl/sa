<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
        $exceptions->render(function (NotFoundHttpException $e) {
            if (request()->ajax()) {
                return response()->json([
                    'msg' => $e->getMessage(),
                    'code' => 1,
                    'data' => [],
                ], 200);
            }
        });
        $exceptions->render(function (AuthenticationException $e) {
            // ...
            if (request()->ajax()) {
                return response()->json([
                    'msg' => $e->getMessage(),
                    'code' => 1001,
                    'data' => [],
                ], 200);
            }
        });
    })->create();
