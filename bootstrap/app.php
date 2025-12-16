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
    ->withMiddleware(function (Middleware $middleware) {
        // HAPUS/KOMENTARI baris ini agar tidak bentrok dengan SESSION_DRIVER=array
        // $middleware->use([
        //     \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        // ]);

        // Daftarkan middleware custom di sini (seperti RoleMiddleware)
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
        
        // Opsional: Jika nanti butuh CORS yang lebih spesifik, bisa diatur di sini juga,
        // tapi default Laravel sudah cukup pintar membaca config/cors.php.
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();