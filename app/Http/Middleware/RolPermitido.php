<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolPermitido
{
    /**
     * Protege rutas por rol usando la relación oficial User -> role
     * (users.FK_id_rol -> roles.id_rol). NUNCA usa la columna legacy
     * users.rol ni IDs fijos: compara por el nombre real del rol.
     *
     * Uso en rutas:
     *   ->middleware('rol:Administrador')
     *   ->middleware('rol:Administrador,Control Escolar')   (varios roles)
     */
    public function handle(Request $request, Closure $next, string ...$rolesPermitidos): Response
    {
        $user = $request->user();

        // Sin autenticación no hay rol que verificar (lo resuelve 'auth').
        if ($user === null) {
            return redirect()->route('login');
        }

        $nombreRol = $user->role?->nombre;

        $permitidos = array_map(
            fn (string $rol) => mb_strtolower(trim($rol)),
            $rolesPermitidos
        );

        if ($nombreRol === null || ! in_array(mb_strtolower(trim($nombreRol)), $permitidos, true)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
