<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectTo(
            guests: '/login',
            users: '/index2'
        );
        $middleware->web(append: [
            \App\Http\Middleware\InitializeTenantContext::class,
        ]);
        $middleware->api(append: [
            \App\Http\Middleware\InitializeTenantContext::class,
        ]);

        // Register the 'tenant' middleware alias
        $middleware->alias([
            'tenant' => \App\Http\Middleware\InitializeTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
