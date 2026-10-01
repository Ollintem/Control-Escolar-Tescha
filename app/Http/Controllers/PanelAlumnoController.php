<?php

namespace App\Http\Controllers;

use App\Models\PeriodoEscolar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Panel del Alumno (piloto de redirección por roles).
 *
 * Reglas de este controlador:
 *  - SOLO consulta datos del alumno autenticado (users.id_alumno). No existe
 *    ningún parámetro de ruta/petición que permita ver la información de otro
 *    alumno, así que no hace falta autorizar por ID.
 *  - NO inventa información: si la base de datos no tiene registros (hoy
 *    alumnos = 0, calificaciones = 0, historial = 0, periodos = 0), la vista
 *    muestra el estado vacío correspondiente.
 *  - Si users.id_alumno es NULL (cuenta sin persona/vínculo académico), no se
 *    consulta nada y se muestra el estado vacío explicando la relación faltante.
 */
class PanelAlumnoController extends Controller
{
    /**
     * Inicio del panel: saludo, periodo activo (solo si existe) y contadores
     * reales de la información del alumno.
     */
    public function index()
    {
        $idAlumno = $this->idAlumno();

        return view('panels.alumno.index', [
            'periodoActivo' => $this->periodoActivo(),
            'idAlumno' => $idAlumno,
            'totalInscripciones' => $this->contar($idAlumno, 'inscripciones'),
            'totalCalificaciones' => $idAlumno === null
                ? 0
                : DB::table('calificaciones')
                    ->whereIn(
                        'id_inscripcion',
                        DB::table('inscripciones')->where('id_alumno', $idAlumno)->select('id_inscripcion')
                    )
                    ->count(),
            'totalHistorial' => $this->contar($idAlumno, 'historial_academico'),
        ]);
    }

    /**
     * Mis Calificaciones: parciales capturados de las inscripciones DEL
     * ALUMNO AUTENTICADO (calificaciones.id_inscripcion -> inscripciones.id_alumno).
     */
    public function calificaciones()
    {
        $idAlumno = $this->idAlumno();

        $calificaciones = collect();

        if ($idAlumno !== null) {
            $calificaciones = DB::table('calificaciones as c')
                ->join('inscripciones as i', 'i.id_inscripcion', '=', 'c.id_inscripcion')
                ->leftJoin('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
                ->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia')
                ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo')
                ->where('i.id_alumno', $idAlumno)
                ->orderByDesc('p.clave')
                ->orderBy('c.parcial')
                ->get([
                    'c.parcial',
                    'c.calificacion',
                    'c.observaciones',
                    'm.clave as clave_materia',
                    'm.nombre as materia',
                    'g.nombre as grupo',
                    'p.clave as clave_periodo',
                    'p.nombre as periodo',
                ]);
        }

        return view('panels.alumno.calificaciones', [
            'calificaciones' => $calificaciones,
            'idAlumno' => $idAlumno,
        ]);
    }

    /**
     * Mi Historial Académico: renglones de historial_academico DEL ALUMNO
     * AUTENTICADO (historial_academico.id_alumno).
     */
    public function historial()
    {
        $idAlumno = $this->idAlumno();

        $historial = collect();

        if ($idAlumno !== null) {
            $historial = DB::table('historial_academico as h')
                ->leftJoin('materias as m', 'm.id_materia', '=', 'h.id_materia')
                ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'h.id_periodo')
                ->where('h.id_alumno', $idAlumno)
                ->orderBy('h.id_periodo')
                ->orderBy('m.nombre')
                ->get([
                    'm.clave as clave_materia',
                    'm.nombre as materia',
                    'p.clave as clave_periodo',
                    'p.nombre as periodo',
                    'h.calificacion',
                    'h.tipo_acreditacion',
                    'h.aprobada',
                ]);
        }

        return view('panels.alumno.historial', [
            'historial' => $historial,
            'idAlumno' => $idAlumno,
        ]);
    }

    /**
     * ID del alumno dueño de la sesión. NULL si la cuenta no tiene vinculado
     * users.id_alumno (no se inventa ni se "adivina" un alumno).
     */
    private function idAlumno(): ?int
    {
        $idAlumno = Auth::user()?->id_alumno;

        return $idAlumno === null ? null : (int) $idAlumno;
    }

    /**
     * Periodo escolar activo. Se muestra en el inicio SOLO si la tabla real
     * tiene un periodo con activo = 1; hoy la tabla está vacía (NULL).
     */
    private function periodoActivo(): ?PeriodoEscolar
    {
        return PeriodoEscolar::where('activo', 1)->orderBy('id_periodo')->first();
    }

    private function contar(?int $idAlumno, string $tabla): int
    {
        if ($idAlumno === null) {
            return 0;
        }

        return DB::table($tabla)->where('id_alumno', $idAlumno)->count();
    }
}
