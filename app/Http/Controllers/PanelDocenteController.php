<?php

namespace App\Http\Controllers;

use App\Http\Middleware\DocenteActivo;
use App\Models\Docente;
use App\Models\PeriodoEscolar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Panel Docente (misma arquitectura que el Panel Alumno).
 *
 * Relaciones REALES usadas (verificadas en la BD):
 *   users.id_docente        -> docentes.id_docente   (vínculo de la cuenta)
 *   grupos.id_docente       -> docentes.id_docente   (sus grupos)
 *   inscripciones.id_grupo  -> grupos.id_grupo       (sus alumnos)
 *   calificaciones.id_inscripcion -> inscripciones -> grupos
 *
 * Reglas:
 *  - El perfil docente se verifica en el middleware del grupo de rutas.
 *  - Las consultas se limitan al perfil vinculado al usuario autenticado.
 *  - Cada consulta exige los Gates de los datos que presenta.
 */
class PanelDocenteController extends Controller
{
    /**
     * Inicio: saludo, periodo activo (solo si existe) y contadores REALES.
     */
    public function index()
    {
        $this->exigir('Docentes.ver');

        $docente = $this->docenteAutenticado();
        $idDocente = (int) $docente->id_docente;
        $puedeVerPeriodo = Gate::allows('Periodos Escolares.ver');
        $puedeVerGrupos = Gate::allows('Grupos y Asignaciones.ver');
        $puedeVerAlumnos = Gate::allows('Alumnos.ver')
            && Gate::allows('Inscripciones.ver')
            && Gate::allows('Grupos y Asignaciones.ver');
        $puedeVerCalificaciones = Gate::allows('Calificaciones y Actas.ver')
            && Gate::allows('Grupos y Asignaciones.ver');

        return view('panels.docente.index', [
            'periodoActivo' => $puedeVerPeriodo ? $this->periodoActivo() : null,
            'idDocente' => $idDocente,
            'docente' => $docente,
            'totalGrupos' => $puedeVerGrupos ? $this->contar($idDocente, 'grupos') : '—',
            'totalAlumnos' => $puedeVerAlumnos ? (int) DB::table('inscripciones as i')
                ->join('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
                ->where('g.id_docente', $idDocente)
                ->distinct()
                ->count('i.id_alumno') : '—',
            'totalCalificaciones' => $puedeVerCalificaciones
                ? $this->calificacionesQuery($idDocente)->count()
                : '—',
        ]);
    }

    /**
     * Mis Grupos: grupos donde él es el docente titular (grupos.id_docente).
     */
    public function grupos()
    {
        $this->exigir('Grupos y Asignaciones.ver');

        $idDocente = (int) $this->docenteAutenticado()->id_docente;
        $puedeVerMaterias = Gate::allows('Materias y Plan de Estudios.ver');
        $puedeVerPeriodos = Gate::allows('Periodos Escolares.ver');

        $query = DB::table('grupos as g')->where('g.id_docente', $idDocente);
        $columnas = [
            'g.nombre as grupo',
            'g.cupo_maximo',
            'g.activo',
        ];

        if ($puedeVerMaterias) {
            $query->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia');
            array_push($columnas, 'm.clave as clave_materia', 'm.nombre as materia', 'm.creditos');
        } else {
            array_push(
                $columnas,
                DB::raw('NULL as clave_materia'),
                DB::raw('NULL as materia'),
                DB::raw('NULL as creditos')
            );
        }

        if ($puedeVerPeriodos) {
            $query->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo');
            array_push($columnas, 'p.clave as clave_periodo', 'p.nombre as periodo');
        } else {
            array_push($columnas, DB::raw('NULL as clave_periodo'), DB::raw('NULL as periodo'));
        }

        $grupos = $query->orderBy('g.nombre')->get($columnas);

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
        $this->exigir('Alumnos.ver', 'Inscripciones.ver', 'Grupos y Asignaciones.ver');

        $idDocente = (int) $this->docenteAutenticado()->id_docente;
        $puedeVerMaterias = Gate::allows('Materias y Plan de Estudios.ver');
        $puedeVerCalificaciones = Gate::allows('Calificaciones y Actas.ver');

        $query = DB::table('inscripciones as i')
            ->join('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
            ->leftJoin('alumnos as a', 'a.id_alumno', '=', 'i.id_alumno')
            ->where('g.id_docente', $idDocente);
        $columnas = [
            'a.no_control',
            'a.nombre as nombre_alumno',
            'a.apellido_paterno',
            'a.apellido_materno',
            'i.estatus',
            'g.nombre as grupo',
        ];

        if ($puedeVerMaterias) {
            $query->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia');
            $columnas[] = 'm.nombre as materia';
        } else {
            $columnas[] = DB::raw('NULL as materia');
        }

        $columnas[] = $puedeVerCalificaciones
            ? 'i.calificacion_final'
            : DB::raw('NULL as calificacion_final');

        $alumnos = $query
            ->orderBy('g.nombre')
            ->orderBy('a.apellido_paterno')
            ->get($columnas);

        return view('panels.docente.alumnos', [
            'alumnos' => $alumnos,
            'idDocente' => $idDocente,
        ]);
    }

    /**
     * Calificaciones: parciales inscritos en grupos asignados al docente autenticado.
     */
    public function calificaciones()
    {
        $this->exigir('Calificaciones y Actas.ver');

        $idDocente = (int) $this->docenteAutenticado()->id_docente;
        $puedeVerAlumnos = Gate::allows('Alumnos.ver');
        $puedeVerGrupos = Gate::allows('Grupos y Asignaciones.ver');
        $puedeVerMaterias = Gate::allows('Materias y Plan de Estudios.ver');

        $query = $this->calificacionesQuery($idDocente);
        $columnas = [
            'c.parcial',
            'c.calificacion',
            'c.observaciones',
        ];

        if ($puedeVerAlumnos) {
            $query->leftJoin('alumnos as a', 'a.id_alumno', '=', 'i.id_alumno');
            array_push($columnas, 'a.no_control', 'a.nombre as nombre_alumno', 'a.apellido_paterno');
        } else {
            array_push(
                $columnas,
                DB::raw('NULL as no_control'),
                DB::raw('NULL as nombre_alumno'),
                DB::raw('NULL as apellido_paterno')
            );
        }

        if ($puedeVerGrupos) {
            $columnas[] = 'g.nombre as grupo';
        } else {
            $columnas[] = DB::raw('NULL as grupo');
        }

        if ($puedeVerMaterias) {
            $query->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia');
            $columnas[] = 'm.nombre as materia';
        } else {
            $columnas[] = DB::raw('NULL as materia');
        }

        $calificaciones = $query
            ->orderBy('g.nombre')
            ->orderBy('c.parcial')
            ->get($columnas);

        return view('panels.docente.calificaciones', [
            'calificaciones' => $calificaciones,
            'idDocente' => $idDocente,
        ]);
    }

    private function docenteAutenticado(): Docente
    {
        $docente = request()->attributes->get(DocenteActivo::DOCENTE_ATTRIBUTE);

        abort_unless($docente instanceof Docente, 403);

        return $docente;
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
            ->where('g.id_docente', $idDocente);
    }

    private function exigir(string ...$habilidades): void
    {
        foreach ($habilidades as $habilidad) {
            abort_unless(
                Gate::allows($habilidad),
                403,
                'No tienes permiso para consultar esta información.'
            );
        }
    }
}
