<?php

use App\Exceptions\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // $middleware->statefulApi();
        $middleware->redirectGuestsTo(fn() => abort(401, 'Unauthenticated'));
        $middleware->redirectUsersTo(fn() => abort(403, 'Already authenticated'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e) {
            return (new ExceptionHandler())->handle($e);
        });
    })->create();
