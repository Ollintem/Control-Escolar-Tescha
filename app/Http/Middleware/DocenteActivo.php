<?php

namespace App\Http\Middleware;

use App\Models\Docente;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DocenteActivo
{
    public const DOCENTE_ATTRIBUTE = 'panel_docente.perfil';

    /**
     * The route group applies auth, rol:Docente, and the global UsuarioActivo middleware.
     * This middleware validates the account's linked academic profile.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null) {
            return redirect()->route('login');
        }

        if ($usuario->id_docente === null) {
            return $this->accesoRestringido('Tu cuenta no tiene un perfil docente vinculado. Contacta a Control Escolar para completar la vinculación.');
        }

        $docente = Docente::query()->find($usuario->id_docente);

        if ($docente === null) {
            return $this->accesoRestringido('No se encontró un perfil docente válido para esta cuenta. Contacta a Control Escolar para revisar la vinculación.');
        }

        if (! $docente->activo) {
            return $this->accesoRestringido('Tu perfil docente está inactivo. Contacta a Control Escolar para solicitar su reactivación.');
        }

        $request->attributes->set(self::DOCENTE_ATTRIBUTE, $docente);

        return $next($request);
    }

    private function accesoRestringido(string $mensaje): Response
    {
        return response()->view('panels.alumno.acceso-restringido', [
            'mensaje' => $mensaje,
        ], 403);
    }
}
