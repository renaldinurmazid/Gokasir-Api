<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__.'/../routes/web.php',
            __DIR__.'/../routes/admin.php',
        ],
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Guest di area admin diarahkan ke login admin; admin yang sudah login tidak perlu ke halaman login.
        $middleware->redirectGuestsTo(fn ($request) => $request->is('admin', 'admin/*') ? route('admin.login') : '/');
        $middleware->redirectUsersTo(fn ($request) => $request->is('admin', 'admin/*') ? route('admin.dashboard') : '/');

        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'owner'  => \App\Http\Middleware\EnsureOwner::class,
            'activated' => \App\Http\Middleware\EnsureActivated::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
