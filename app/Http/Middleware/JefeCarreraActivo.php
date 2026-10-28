<?php

namespace App\Http\Middleware;

use App\Models\JefeCarrera;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class JefeCarreraActivo
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

        $jefeId = $usuario->id_jefe_carrera;

        if ($jefeId === null) {
            return $this->accesoRestringido('La cuenta no tiene un perfil de Jefe de Carrera vinculado.');
        }

        $jefe = JefeCarrera::query()
            ->with('carrera')
            ->find($jefeId);

        if ($jefe === null) {
            return $this->accesoRestringido('No se encontró un perfil de Jefe de Carrera válido para esta cuenta.');
        }

        if (! $jefe->activo) {
            return $this->accesoRestringido('El perfil de Jefe de Carrera está inactivo. Contacta a Administración para solicitar la reactivación.');
        }

        if ($jefe->carrera === null) {
            return $this->accesoRestringido('El perfil no tiene una carrera válida asignada. Contacta a Administración.');
        }

        if (! $jefe->carrera->activo) {
            return $this->accesoRestringido('La carrera asignada está inactiva. El acceso académico no está disponible; contacta a Administración.');
        }

        return $next($request);
    }

    private function accesoRestringido(string $mensaje): Response
    {
        return response()->view('panels.jefe-carrera.acceso-restringido', [
            'mensaje' => $mensaje,
        ], 403);
    }
}
