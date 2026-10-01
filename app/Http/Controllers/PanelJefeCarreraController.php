<?php

namespace App\Http\Controllers;

use App\Models\PeriodoEscolar;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Panel de Jefe de Carrera (misma arquitectura que Alumno y Docente).
 *
 * Relaciones REALES usadas (verificadas en la BD):
 *   users.id_jefe_carrera -> jefes_carrera.id_jefe_carrera -> jefes_carrera.id_carrera
 *   materias.id_carrera / plan_estudios.id_carrera / semestres.id_carrera / alumnos.id_carrera
 *   grupos.id_materia -> materias.id_carrera                (grupos de MI carrera)
 *   inscripciones.id_grupo -> grupos.id_materia -> materias.id_carrera
 *   calificaciones.id_inscripcion -> inscripciones ...
 *   historial_academico.id_alumno -> alumnos.id_carrera
 *   docentes.id_docente -> grupos.id_docente -> grupos.id_materia -> materias.id_carrera
 *     (NO existe docentes.id_carrera: el único vínculo real es por grupos/materias)
 *
 * Reglas:
 *  - Todo se filtra por LA CARRERA DEL JEFE; sin IDs fijos ni carrera hardcodeada.
 *  - Si users.id_jefe_carrera es NULL, no se consulta nada (estado vacío).
 *  - Cada página exige su permiso de la Matriz EN EL SERVIDOR con Gate
 *    ("Modulo.accion"), además de ocultar los botones en la vista: la seguridad
 *    no depende de esconder botones.
 *  - No se exponen rutas de escritura en esta primera versión.
 */
class PanelJefeCarreraController extends Controller
{
    /**
     * Inicio: carrera, jefe, periodo activo (solo si existe) y contadores
     * reales de MI carrera.
     */
    public function index()
    {
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.index', [
            'periodoActivo' => $this->periodoActivo(),
            'jefe' => $ctx['jefe'],
            'carrera' => $ctx['carrera'],
            'idCarrera' => $idCarrera,
            'totalMaterias' => $this->contar('materias', $idCarrera),
            'totalDocentes' => $idCarrera === null ? 0 : $this->docentesQuery($idCarrera)->count('d.id_docente'),
            'totalGrupos' => $idCarrera === null ? 0 : $this->gruposQuery($idCarrera)->count(),
            'totalAlumnos' => $this->contar('alumnos', $idCarrera),
            'totalCalificaciones' => $idCarrera === null ? 0 : $this->calificacionesQuery($idCarrera)->count(),
            'totalHistorial' => $idCarrera === null ? 0 : $this->historialQuery($idCarrera)->count(),
        ]);
    }

    /** Materias y Plan de Estudios de MI carrera. */
    public function materiasPlan()
    {
        $this->exigir('Materias y Plan de Estudios');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.materias-plan', [
            'materias' => $idCarrera === null ? collect() : DB::table('materias')
                ->where('id_carrera', $idCarrera)->orderBy('clave')->get(),
            'planes' => $idCarrera === null ? collect() : DB::table('plan_estudios')
                ->where('id_carrera', $idCarrera)->orderBy('clave')->get(),
            'idCarrera' => $idCarrera,
        ]);
    }

    /** Docentes ligados a MI carrera por grupos/materias (no existe docentes.id_carrera). */
    public function docentes()
    {
        $this->exigir('Docentes');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.docentes', [
            'docentes' => $idCarrera === null ? collect() : $this->docentesQuery($idCarrera)
                ->orderBy('d.apellido_paterno')
                ->get([
                    'd.id_docente',
                    'd.no_empleado',
                    'd.nombre',
                    'd.apellido_paterno',
                    'd.apellido_materno',
                    'd.email',
                    'd.activo',
                ]),
            'idCarrera' => $idCarrera,
        ]);
    }

    /** Grupos cuya materia pertenece a MI carrera. */
    public function grupos()
    {
        $this->exigir('Grupos y Asignaciones');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.grupos', [
            'grupos' => $idCarrera === null ? collect() : DB::table('grupos as g')
                ->join('materias as m', 'm.id_materia', '=', 'g.id_materia')
                ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo')
                ->where('m.id_carrera', $idCarrera)
                ->orderBy('g.nombre')
                ->get([
                    'g.nombre as grupo',
                    'g.cupo_maximo',
                    'g.activo',
                    'm.nombre as materia',
                    'm.clave as clave_materia',
                    'p.nombre as periodo',
                ]),
            'idCarrera' => $idCarrera,
        ]);
    }

    /** Alumnos inscritos en MI carrera (alumnos.id_carrera). */
    public function alumnos()
    {
        $this->exigir('Alumnos');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.alumnos', [
            'alumnos' => $idCarrera === null ? collect() : DB::table('alumnos')
                ->where('id_carrera', $idCarrera)
                ->orderBy('apellido_paterno')->get(),
            'idCarrera' => $idCarrera,
        ]);
    }

    /** Calificaciones de inscripciones de grupos de MI carrera. */
    public function calificaciones()
    {
        $this->exigir('Calificaciones y Actas');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.calificaciones', [
            'calificaciones' => $idCarrera === null ? collect() : $this->calificacionesQuery($idCarrera)
                ->orderBy('g.nombre')
                ->orderBy('c.parcial')
                ->get([
                    'c.parcial',
                    'c.calificacion',
                    'c.observaciones',
                    'a.no_control',
                    'a.nombre as nombre_alumno',
                    'a.apellido_paterno',
                    'a.apellido_materno',
                    'g.nombre as grupo',
                    'm.nombre as materia',
                    'm.clave as clave_materia',
                ]),
            'idCarrera' => $idCarrera,
        ]);
    }

    /** Historial académico de alumnos de MI carrera. */
    public function historial()
    {
        $this->exigir('Historial Académico');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.historial', [
            'historial' => $idCarrera === null ? collect() : $this->historialQuery($idCarrera)
                ->orderBy('a.apellido_paterno')
                ->orderBy('m.nombre')
                ->get([
                    'a.no_control',
                    'a.nombre as nombre_alumno',
                    'a.apellido_paterno',
                    'a.apellido_materno',
                    'm.clave as clave_materia',
                    'm.nombre as materia',
                    'p.clave as clave_periodo',
                    'p.nombre as periodo',
                    'h.calificacion',
                    'h.tipo_acreditacion',
                    'h.aprobada',
                ]),
            'idCarrera' => $idCarrera,
        ]);
    }

    /**
     * Autorización EN EL SERVIDOR según la Matriz de Permisos
     * (Gate "{Modulo}.{accion}"). Un usuario sin "ver" recibe 403 aunque
     * conozca la URL: la protección no depende de los botones de la vista.
     */
    private function exigir(string $modulo): void
    {
        abort_unless(
            Gate::allows($modulo . '.ver'),
            403,
            'No tienes permiso para acceder a esta sección.'
        );
    }

    /**
     * Vínculo real de la cuenta con su carrera.
     * Devuelve todo NULL si users.id_jefe_carrera no está vinculado.
     */
    private function contexto(): array
    {
        $idJefe = Auth::user()?->id_jefe_carrera;
        $jefe = null;
        $carrera = null;

        if ($idJefe !== null) {
            $jefe = DB::table('jefes_carrera')->where('id_jefe_carrera', (int) $idJefe)->first();

            if ($jefe !== null && $jefe->id_carrera !== null) {
                $carrera = DB::table('carreras')->where('id_carrera', $jefe->id_carrera)->first();
            }
        }

        return [
            'jefe' => $jefe,
            'id_carrera' => $carrera !== null ? (int) $carrera->id_carrera : null,
            'carrera' => $carrera,
        ];
    }

    private function periodoActivo(): ?PeriodoEscolar
    {
        return PeriodoEscolar::where('activo', 1)->orderBy('id_periodo')->first();
    }

    private function contar(string $tabla, ?int $idCarrera): int
    {
        if ($idCarrera === null) {
            return 0;
        }

        return DB::table($tabla)->where('id_carrera', $idCarrera)->count();
    }

    private function gruposQuery(?int $idCarrera)
    {
        return DB::table('grupos as g')
            ->join('materias as m', 'm.id_materia', '=', 'g.id_materia')
            ->where('m.id_carrera', $idCarrera);
    }

    private function docentesQuery(?int $idCarrera)
    {
        return DB::table('docentes as d')
            ->join('grupos as g', 'g.id_docente', '=', 'd.id_docente')
            ->join('materias as m', 'm.id_materia', '=', 'g.id_materia')
            ->where('m.id_carrera', $idCarrera)
            ->distinct();
    }

    private function calificacionesQuery(?int $idCarrera)
    {
        return DB::table('calificaciones as c')
            ->join('inscripciones as i', 'i.id_inscripcion', '=', 'c.id_inscripcion')
            ->join('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
            ->join('materias as m', 'm.id_materia', '=', 'g.id_materia')
            ->leftJoin('alumnos as a', 'a.id_alumno', '=', 'i.id_alumno')
            ->where('m.id_carrera', $idCarrera);
    }

    private function historialQuery(?int $idCarrera)
    {
        return DB::table('historial_academico as h')
            ->join('alumnos as a', 'a.id_alumno', '=', 'h.id_alumno')
            ->leftJoin('materias as m', 'm.id_materia', '=', 'h.id_materia')
            ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'h.id_periodo')
            ->where('a.id_carrera', $idCarrera);
    }
}
