<?php

use App\Http\Middleware\EnsureProfileComplete;
use App\Models\Setting;
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
        $middleware->appendToGroup('web', EnsureProfileComplete::class);
        $middleware->redirectGuestsTo(function () {
            return Setting::get('auth_mode', 'login') === 'household'
                ? route('household')
                : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
