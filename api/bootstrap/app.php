<?php

use App\Http\Middleware\AuthenticateBotHmac;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\EnsureAdminProject;
use App\Http\Middleware\EnsureProjectHeader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => AuthenticateJwt::class,
            'project.header' => EnsureProjectHeader::class,
            'bot.hmac' => AuthenticateBotHmac::class,
            'admin.project' => EnsureAdminProject::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
