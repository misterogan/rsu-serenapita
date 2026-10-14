<?php

use App\Http\Middleware\EnsureStaff;
use App\Http\Middleware\ModuleAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'staff' => EnsureStaff::class,
            'module' => ModuleAccess::class,
            'page' => \App\Http\Middleware\PageEnabled::class,
        ]);
        $middleware->validateCsrfTokens(except: ['api/simrs/webhook']);
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
