<?php

namespace App\Http\Middleware;

use App\Models\Alumno;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AlumnoActivo
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return redirect()->route('login');
        }

        if ($usuario->id_alumno === null) {
            return $this->accesoRestringido('La cuenta no tiene un perfil de alumno vinculado. Contacta a Servicios Escolares para revisar la cuenta.');
        }

        $alumno = Alumno::query()->find($usuario->id_alumno);

        if ($alumno === null) {
            return $this->accesoRestringido('No se encontró un perfil de alumno válido para esta cuenta. Contacta a Servicios Escolares.');
        }

        if (! $alumno->activo) {
            return $this->accesoRestringido('El perfil académico está inactivo. Contacta a Servicios Escolares para solicitar su reactivación.');
        }

        return $next($request);
    }

    private function accesoRestringido(string $mensaje): Response
    {
        return response()->view('panels.alumno.acceso-restringido', [
            'mensaje' => $mensaje,
        ], 403);
    }
}
