<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UsuarioActivo
{
    /**
     * Impide que un usuario autenticado pero inactivo (activo = 0) continúe
     * utilizando el sistema: se cierra su sesión de forma segura (logout,
     * invalidación de sesión y token) y se redirige al login con un mensaje
     * comprensible.
     *
     * Registrado como alias 'activo' y adjunto al grupo web en bootstrap/app.php,
     * por lo que cubre todas las rutas autenticadas (dashboard, permisos,
     * peticiones Livewire, etc.).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Sin usuario autenticado no hay nada que validar (lo resuelve 'auth').
        if ($user !== null && ! $user->activo) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'form.email' => 'Tu cuenta está inactiva. Contacta a Servicios Escolares para reactivarla.',
                ]);
        }

        return $next($request);
    }
}
