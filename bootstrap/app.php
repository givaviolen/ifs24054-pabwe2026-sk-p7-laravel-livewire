<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(\App\Http\Middleware\AllowBackForwardCache::class);

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'owns.stock' => \App\Http\Middleware\EnsureStockOwnership::class,
        ]);

        // User yang sudah login yang membuka halaman guest (/, /login, /register)
        // diarahkan ke dashboard sesuai role.
        $middleware->redirectUsersTo(fn (Request $request) => $request->user()?->isAdmin()
            ? route('admin.users')
            : route('stocks.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();