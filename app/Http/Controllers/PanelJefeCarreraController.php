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
 *     (docentes que IMPARTEN clases en la carrera)
 *   carrera_docente (id_carrera, id_docente) -> docentes
 *     (docentes que PERTENECEN formalmente a la carrera)
 *
 * Los dos conceptos se muestran por separado en el modulo Docentes:
 *   A) PERTENECEN  -> tabla intermedia carrera_docente (incluso sin grupos)
 *   B) IMPARTEN    -> docentes -> grupos -> materias -> carreras
 * Un docente puede impartir en una carrera sin pertenecer formalmente a ella.
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
        // El dashboard resume datos de estos módulos; exige sus permisos de
        // consulta para que sus contadores no se conviertan en un bypass.
        foreach ([
            'Jefes de Carrera',
            'Carreras y Semestres',
            'Periodos Escolares',
            'Materias y Plan de Estudios',
            'Docentes',
            'Grupos y Asignaciones',
            'Alumnos',
            'Calificaciones y Actas',
            'Historial Académico',
        ] as $modulo) {
            $this->exigir($modulo);
        }

        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        return view('panels.jefe-carrera.index', [
            'periodoActivo' => $this->periodoActivo(),
            'jefe' => $ctx['jefe'],
            'carrera' => $ctx['carrera'],
            'idCarrera' => $idCarrera,
            'totalMaterias' => $this->contar('materias', $idCarrera),
            'totalDocentesPertenecen' => $idCarrera === null ? 0 : $this->docentesPertenecenQuery($idCarrera)->count(),
            'totalDocentesImparten' => $idCarrera === null ? 0 : $this->docentesImpartenQuery($idCarrera)->distinct()->count('d.id_docente'),
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

    /**
     * Docentes de MI carrera: dos listas distintas.
     *
     *  A) docentesPertenecen -> PERTENECEN formalmente (carrera_docente),
     *     aunque todavía no tengan grupos asignados.
     *  B) docentesImparten   -> IMPARTEN clases aqui (grupos -> materias),
     *     con su información académica. Pueden no pertenecer a la carrera.
     */
    public function docentes()
    {
        $this->exigir('Docentes');
        $ctx = $this->contexto();
        $idCarrera = $ctx['id_carrera'];

        $columnas = [
            'd.id_docente',
            'd.no_empleado',
            'd.nombre',
            'd.apellido_paterno',
            'd.apellido_materno',
            'd.email',
            'd.activo',
        ];

        return view('panels.jefe-carrera.docentes', [
            'docentesPertenecen' => $idCarrera === null
                ? collect()
                : $this->docentesPertenecenQuery($idCarrera)
                    ->orderBy('d.apellido_paterno')
                    ->get($columnas),

            'docentesImparten' => $idCarrera === null
                ? collect()
                : $this->docentesImpartenQuery($idCarrera)
                    ->groupBy(
                        'd.id_docente',
                        'd.no_empleado',
                        'd.nombre',
                        'd.apellido_paterno',
                        'd.apellido_materno',
                        'd.email',
                        'd.activo'
                    )
                    ->orderBy('d.apellido_paterno')
                    ->get(array_merge($columnas, [
                        DB::raw('COUNT(DISTINCT g.id_grupo) as grupos_impartidos'),
                        DB::raw('COUNT(DISTINCT m.id_materia) as materias_impartidas'),
                        DB::raw("GROUP_CONCAT(DISTINCT m.nombre ORDER BY m.nombre SEPARATOR ' · ') as nombres_materias"),
                    ])),

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
            Gate::allows($modulo.'.ver'),
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

    /**
     * A) Docentes que PERTENECEN a MI carrera (N:N `carrera_docente`).
     *
     * Es la pertenencia formal registrada desde los módulos Docentes o
     * Usuarios y Roles: NO se deduce de materias ni grupos. Por eso aqui
     * aparecen tambien los docentes que todavía no tienen grupos.
     *
     * `distinct()` evita filas repetidas si un docente estuviera en la
     * tabla varias veces.
     */
    private function docentesPertenecenQuery(?int $idCarrera)
    {
        return DB::table('docentes as d')
            ->join('carrera_docente as cd', 'cd.id_docente', '=', 'd.id_docente')
            ->where('cd.id_carrera', $idCarrera)
            ->distinct();
    }

    /**
     * B) Docentes que IMPARTEN clases en MI carrera: siempre a través de
     *    las asignaciones académicas (grupos -> materias -> carrera).
     *
     * Un docente puede impartir aquí sin pertenecer formalmente a la
     * carrera, por lo que esta consulta es independiente de `carrera_docente`.
     */
    private function docentesImpartenQuery(?int $idCarrera)
    {
        return DB::table('docentes as d')
            ->join('grupos as g', 'g.id_docente', '=', 'd.id_docente')
            ->join('materias as m', 'm.id_materia', '=', 'g.id_materia')
            ->where('m.id_carrera', $idCarrera);
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
