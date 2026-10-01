<?php

use App\Http\Middleware\RolPermitido;
use App\Http\Middleware\UsuarioActivo;
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
        $middleware->alias([
            'activo' => UsuarioActivo::class,
            'rol' => RolPermitido::class,
        ]);

        // Toda petición web (tras iniciar sesión/cookies) valida que la cuenta
        // esté activa: usuarios con activo = 0 no pueden usar el sistema.
        $middleware->web(append: [UsuarioActivo::class]);

        // Un usuario AUTENTICADO que visita una ruta de invitado (/login)
        // vuelve a SU panel, no a /dashboard. Se resuelve por el nombre del
        // rol (User::rutaPanel), nunca por IDs.
        $middleware->redirectUsersTo(
            fn (Request $request) => $request->user()?->rutaPanel() ?? '/'
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
