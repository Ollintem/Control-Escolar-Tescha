<?php

namespace App\Http\Controllers;

use App\Models\PeriodoEscolar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Panel Docente (misma arquitectura que el Panel Alumno).
 *
 * Relaciones REALES usadas (verificadas en la BD):
 *   users.id_docente        -> docentes.id_docente   (vínculo de la cuenta)
 *   grupos.id_docente       -> docentes.id_docente   (sus grupos)
 *   inscripciones.id_grupo  -> grupos.id_grupo       (sus alumnos)
 *   calificaciones.id_inscripcion -> inscripciones   (calificaciones)
 *   calificaciones.id_capturada_por -> docentes      (lo que él capturó)
 *
 * Reglas:
 *  - SOLO consulta información ligada al docente autenticado; no existe ningún
 *    parámetro de ruta/petición que permita ver grupos o alumnos de otro.
 *  - NO inventa datos ni relaciones: si users.id_docente es NULL, o las tablas
 *    están vacías, se muestran los estados vacíos correspondientes.
 *  - No se modifican tablas, roles ni permisos para esto.
 */
class PanelDocenteController extends Controller
{
    /**
     * Inicio: saludo, periodo activo (solo si existe) y contadores REALES.
     */
    public function index()
    {
        $idDocente = $this->idDocente();

        return view('panels.docente.index', [
            'periodoActivo' => $this->periodoActivo(),
            'idDocente' => $idDocente,
            'docente' => $this->registroDocente($idDocente),
            'totalGrupos' => $this->contar($idDocente, 'grupos'),
            'totalAlumnos' => $idDocente === null ? 0 : (int) DB::table('inscripciones as i')
                ->join('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
                ->where('g.id_docente', $idDocente)
                ->distinct()
                ->count('i.id_alumno'),
            'totalCalificaciones' => $idDocente === null ? 0 : $this->calificacionesQuery($idDocente)->count(),
        ]);
    }

    /**
     * Mis Grupos: grupos donde él es el docente titular (grupos.id_docente).
     */
    public function grupos()
    {
        $idDocente = $this->idDocente();

        $grupos = collect();

        if ($idDocente !== null) {
            $grupos = DB::table('grupos as g')
                ->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia')
                ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo')
                ->where('g.id_docente', $idDocente)
                ->orderBy('g.nombre')
                ->get([
                    'g.nombre as grupo',
                    'g.cupo_maximo',
                    'g.activo',
                    'm.clave as clave_materia',
                    'm.nombre as materia',
                    'm.creditos',
                    'p.clave as clave_periodo',
                    'p.nombre as periodo',
                ]);
        }

        return view('panels.docente.grupos', [
            'grupos' => $grupos,
            'idDocente' => $idDocente,
        ]);
    }

    /**
     * Mis Alumnos: inscripciones de SUS grupos -> alumnos.
     * Si el grupo no tiene inscripciones (hoy: 0), estado vacío.
     */
    public function alumnos()
    {
        $idDocente = $this->idDocente();

        $alumnos = collect();

        if ($idDocente !== null) {
            $alumnos = DB::table('inscripciones as i')
                ->join('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
                ->leftJoin('alumnos as a', 'a.id_alumno', '=', 'i.id_alumno')
                ->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia')
                ->where('g.id_docente', $idDocente)
                ->orderBy('g.nombre')
                ->orderBy('a.apellido_paterno')
                ->get([
                    'a.no_control',
                    'a.nombre as nombre_alumno',
                    'a.apellido_paterno',
                    'a.apellido_materno',
                    'g.nombre as grupo',
                    'm.nombre as materia',
                    'i.estatus',
                    'i.calificacion_final',
                ]);
        }

        return view('panels.docente.alumnos', [
            'alumnos' => $alumnos,
            'idDocente' => $idDocente,
        ]);
    }

    /**
     * Calificaciones: parciales de SUS grupos o los que él mismo capturó
     * (calificaciones.id_capturada_por = docente autenticado).
     */
    public function calificaciones()
    {
        $idDocente = $this->idDocente();

        $calificaciones = collect();

        if ($idDocente !== null) {
            $calificaciones = $this->calificacionesQuery($idDocente)
                ->orderBy('g.nombre')
                ->orderBy('c.parcial')
                ->get([
                    'c.parcial',
                    'c.calificacion',
                    'c.observaciones',
                    'a.no_control',
                    'a.nombre as nombre_alumno',
                    'a.apellido_paterno',
                    'g.nombre as grupo',
                    'm.nombre as materia',
                ]);
        }

        return view('panels.docente.calificaciones', [
            'calificaciones' => $calificaciones,
            'idDocente' => $idDocente,
        ]);
    }

    /**
     * Vínculo users.id_docente -> docentes.id_docente.
     * NULL si la cuenta no tiene vinculado un registro docente.
     */
    private function idDocente(): ?int
    {
        $idDocente = Auth::user()?->id_docente;

        return $idDocente === null ? null : (int) $idDocente;
    }

    private function registroDocente(?int $idDocente): ?object
    {
        if ($idDocente === null) {
            return null;
        }

        return DB::table('docentes')->where('id_docente', $idDocente)->first();
    }

    private function periodoActivo(): ?PeriodoEscolar
    {
        return PeriodoEscolar::where('activo', 1)->orderBy('id_periodo')->first();
    }

    private function contar(?int $idDocente, string $tabla): int
    {
        if ($idDocente === null) {
            return 0;
        }

        return DB::table($tabla)->where('id_docente', $idDocente)->count();
    }

    private function calificacionesQuery(int $idDocente)
    {
        return DB::table('calificaciones as c')
            ->join('inscripciones as i', 'i.id_inscripcion', '=', 'c.id_inscripcion')
            ->join('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
            ->leftJoin('alumnos as a', 'a.id_alumno', '=', 'i.id_alumno')
            ->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia')
            ->where(fn ($q) => $q->where('g.id_docente', $idDocente)
                ->orWhere('c.id_capturada_por', $idDocente));
    }
}
