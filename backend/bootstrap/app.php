<?php

if (empty($_ENV['APP_KEY'] ?? getenv('APP_KEY'))) {
    $key = 'base64:' . base64_encode(random_bytes(32));
    putenv("APP_KEY={$key}");
    $_ENV['APP_KEY'] = $key;
}

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
        // API: return 401 JSON instead of redirecting to missing login route
        Authenticate::redirectUsing(fn (Request $request) => $request->is('api*') ? null : '/login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e) {
            if (! config('app.debug')) {
                return response(
                    '500: ' . $e->getMessage() . "\n\nFile: " . $e->getFile() . ':' . $e->getLine(),
                    500,
                    ['Content-Type' => 'text/plain; charset=UTF-8']
                );
            }
        });
    })->create();
