<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sprint 5: Meta no envía token CSRF; el webhook se protege con la firma X-Hub-Signature-256.
        $middleware->validateCsrfTokens(except: ['webhook/whatsapp']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
