<?php

use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\NormalizeEmail;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => IsAdmin::class,
        ]);

        $middleware->append(NormalizeEmail::class);

        // Après un changement ou une réinitialisation du mot de passe, les sessions ouvertes
        // sur les autres appareils sont déconnectées (la session courante est conservée).
        $middleware->web(append: [AuthenticateSession::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
