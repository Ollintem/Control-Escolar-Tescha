<?php

namespace App\Livewire;

use App\Models\Alumno;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Materia;
use App\Models\PeriodoEscolar;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class GestionInscripciones extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public string $filtroPeriodo = '';

    public string $filtroGrupo = '';

    public string $busquedaAlumno = '';

    public string $alumnoId = '';

    public string $periodoId = '';

    public string $grupoId = '';

    #[Locked]
    public array $periodosFiltro = [];

    #[Locked]
    public array $periodosActivos = [];

    #[Locked]
    public array $alumnosDisponibles = [];

    #[Locked]
    public array $gruposDisponibles = [];

    public function mount(): void
    {
        $this->autorizar('ver');

        $this->periodosFiltro = PeriodoEscolar::query()
            ->orderByDesc('clave')
            ->get(['id_periodo', 'clave', 'nombre', 'estado', 'activo'])
            ->toArray();

        if (Gate::allows('Inscripciones.crear')) {
            $this->autorizar('crear');
            $this->cargarPeriodosActivos();
            $this->periodoId = (string) ($this->periodosActivos[0]['id_periodo'] ?? '');
            $this->cargarAlumnosDisponibles();
        }
    }

    public function updatedBusqueda(): void
    {
        $this->autorizar('ver');
        $this->resetPage('inscripcionesPage');
    }

    public function updatedFiltroPeriodo(): void
    {
        $this->autorizar('ver');
        $this->resetPage('inscripcionesPage');
    }

    public function updatedFiltroGrupo(): void
    {
        $this->autorizar('ver');
        $this->resetPage('inscripcionesPage');
    }

    public function updatedBusquedaAlumno(): void
    {
        $this->autorizar('ver');
        $this->autorizar('crear');
        $this->cargarAlumnosDisponibles();
    }

    public function updatedAlumnoId(): void
    {
        $this->autorizar('ver');
        $this->autorizar('crear');
        $this->cargarGruposDisponibles();
    }

    public function updatedPeriodoId(): void
    {
        $this->autorizar('ver');
        $this->autorizar('crear');
        $this->cargarGruposDisponibles();
    }

    public function render()
    {
        $this->autorizar('ver');

        return view('admin.inscripciones.index', [
            'inscripciones' => $this->consultaInscripciones(),
        ]);
    }

    public function guardar(): void
    {
        $this->autorizar('crear');

        $this->resetValidation();
        $validated = $this->validate([
            'alumnoId' => [
                'required',
                'integer',
                Rule::exists('alumnos', 'id_alumno')->where('activo', true),
            ],
            'periodoId' => [
                'required',
                'integer',
                Rule::exists('periodo_escolars', 'id_periodo')
                    ->where('estado', 'Activo')
                    ->where('activo', true),
            ],
            'grupoId' => [
                'required',
                'integer',
                Rule::exists('grupos', 'id_grupo')->where('activo', true),
            ],
        ], [
            'alumnoId.required' => 'Selecciona un alumno activo.',
            'alumnoId.exists' => 'El alumno seleccionado no existe o está inactivo.',
            'periodoId.required' => 'Selecciona un periodo escolar Activo.',
            'periodoId.exists' => 'El periodo seleccionado no está Activo.',
            'grupoId.required' => 'Selecciona un grupo con cupo disponible.',
            'grupoId.exists' => 'El grupo seleccionado no existe o está inactivo.',
        ]);

        $alumnoId = (int) $validated['alumnoId'];
        $periodoId = (int) $validated['periodoId'];
        $grupoId = (int) $validated['grupoId'];

        try {
            $resultado = DB::transaction(function () use ($alumnoId, $periodoId, $grupoId): string {
                $alumno = Alumno::query()
                    ->whereKey($alumnoId)
                    ->lockForUpdate()
                    ->first();

                if ($alumno === null || ! $alumno->activo) {
                    return 'alumno_no_disponible';
                }

                $grupo = Grupo::query()
                    ->whereKey($grupoId)
                    ->lockForUpdate()
                    ->first();

                if ($grupo === null || ! $grupo->activo) {
                    return 'grupo_no_disponible';
                }

                $materia = Materia::query()
                    ->whereKey($grupo->id_materia)
                    ->lockForUpdate()
                    ->first();

                if ($materia === null || ! $materia->activo) {
                    return 'materia_no_disponible';
                }

                if ((int) $alumno->id_carrera !== (int) $materia->id_carrera) {
                    return 'carrera_incompatible';
                }

                $periodo = PeriodoEscolar::query()
                    ->whereKey($grupo->id_periodo)
                    ->lockForUpdate()
                    ->first();

                if (
                    $periodo === null
                    || (int) $grupo->id_periodo !== $periodoId
                    || $periodo->estado !== 'Activo'
                    || ! $periodo->activo
                ) {
                    return 'periodo_no_disponible';
                }

                if ($grupo->id_docente !== null) {
                    $docente = Docente::query()
                        ->whereKey($grupo->id_docente)
                        ->lockForUpdate()
                        ->first();

                    if ($docente === null || ! $docente->activo) {
                        return 'docente_no_disponible';
                    }
                }

                if (Inscripcion::query()
                    ->where('id_alumno', $alumnoId)
                    ->where('id_grupo', $grupoId)
                    ->exists()) {
                    return 'duplicada';
                }

                $inscripcionesActuales = Inscripcion::query()
                    ->where('id_grupo', $grupoId)
                    ->count();

                if ($inscripcionesActuales >= (int) $grupo->cupo_maximo) {
                    return 'cupo_lleno';
                }

                Inscripcion::create([
                    'id_alumno' => $alumnoId,
                    'id_grupo' => $grupoId,
                    'fecha_inscripcion' => now()->toDateString(),
                    'estatus' => 'ordinario',
                ]);

                return 'registrada';
            });
        } catch (QueryException $exception) {
            report($exception);
            $this->addError('grupoId', 'No se pudo registrar la inscripción por una restricción de integridad. No se aplicaron cambios.');

            return;
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'No se pudo registrar la inscripción. La operación se revirtió sin cambios.');

            return;
        }

        if ($resultado !== 'registrada') {
            $this->mostrarErrorDeRegistro($resultado);

            return;
        }

        $this->alumnoId = '';
        $this->grupoId = '';
        $this->busquedaAlumno = '';
        $this->alumnosDisponibles = [];
        $this->gruposDisponibles = [];
        $this->cargarAlumnosDisponibles();
        $this->resetPage('inscripcionesPage');

        session()->flash('success', 'Inscripción ordinaria registrada correctamente.');
    }

    private function consultaInscripciones(): LengthAwarePaginator
    {
        $query = Inscripcion::query()
            ->with([
                'alumno.carrera',
                'grupo.materia',
                'grupo.docente',
                'grupo.periodo',
            ]);

        $busqueda = trim(mb_substr($this->busqueda, 0, 100));

        if ($busqueda !== '') {
            $query->whereHas('alumno', function (Builder $alumnoQuery) use ($busqueda): void {
                $alumnoQuery->where('no_control', 'like', "%{$busqueda}%")
                    ->orWhere('nombre', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                    ->orWhere('apellido_materno', 'like', "%{$busqueda}%");
            });
        }

        if (ctype_digit($this->filtroPeriodo) && (int) $this->filtroPeriodo > 0) {
            $periodoId = (int) $this->filtroPeriodo;
            $query->whereHas('grupo', fn (Builder $grupoQuery) => $grupoQuery->where('id_periodo', $periodoId));
        }

        $filtroGrupo = trim(mb_substr($this->filtroGrupo, 0, 100));

        if ($filtroGrupo !== '') {
            $query->whereHas('grupo', function (Builder $grupoQuery) use ($filtroGrupo): void {
                $grupoQuery->where('nombre', 'like', "%{$filtroGrupo}%")
                    ->orWhereHas('materia', function (Builder $materiaQuery) use ($filtroGrupo): void {
                        $materiaQuery->where('nombre', 'like', "%{$filtroGrupo}%")
                            ->orWhere('clave', 'like', "%{$filtroGrupo}%");
                    });
            });
        }

        return $query
            ->orderByDesc('fecha_inscripcion')
            ->orderByDesc('id_inscripcion')
            ->paginate(15, ['*'], 'inscripcionesPage');
    }

    private function cargarPeriodosActivos(): void
    {
        $this->periodosActivos = PeriodoEscolar::query()
            ->where('estado', 'Activo')
            ->where('activo', true)
            ->orderByDesc('clave')
            ->get(['id_periodo', 'clave', 'nombre', 'estado', 'activo'])
            ->toArray();
    }

    private function cargarAlumnosDisponibles(): void
    {
        $busqueda = trim(mb_substr($this->busquedaAlumno, 0, 100));
        $alumnoSeleccionadoId = ctype_digit($this->alumnoId) && (int) $this->alumnoId > 0
            ? (int) $this->alumnoId
            : null;

        $query = Alumno::query()
            ->with('carrera:id_carrera,clave,nombre')
            ->where('activo', true);

        if ($busqueda !== '' || $alumnoSeleccionadoId !== null) {
            $query->where(function (Builder $criterios) use ($busqueda, $alumnoSeleccionadoId): void {
                if ($busqueda !== '') {
                    $criterios->where(function (Builder $busquedaQuery) use ($busqueda): void {
                        $busquedaQuery->where('no_control', 'like', "%{$busqueda}%")
                            ->orWhere('nombre', 'like', "%{$busqueda}%")
                            ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                            ->orWhere('apellido_materno', 'like', "%{$busqueda}%");
                    });
                }

                if ($alumnoSeleccionadoId !== null) {
                    if ($busqueda !== '') {
                        $criterios->orWhereKey($alumnoSeleccionadoId);
                    } else {
                        $criterios->whereKey($alumnoSeleccionadoId);
                    }
                }
            });
        }

        $this->alumnosDisponibles = $query
            ->orderBy('no_control')
            ->limit(20)
            ->get([
                'id_alumno',
                'id_carrera',
                'no_control',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'activo',
            ])
            ->toArray();
    }

    private function cargarGruposDisponibles(): void
    {
        $this->grupoId = '';
        $this->gruposDisponibles = [];

        if (
            ! ctype_digit($this->alumnoId)
            || ! ctype_digit($this->periodoId)
            || (int) $this->alumnoId < 1
            || (int) $this->periodoId < 1
        ) {
            return;
        }

        $alumno = Alumno::query()
            ->where('activo', true)
            ->find((int) $this->alumnoId);
        $periodo = PeriodoEscolar::query()
            ->where('estado', 'Activo')
            ->where('activo', true)
            ->find((int) $this->periodoId);

        if ($alumno === null || $periodo === null) {
            return;
        }

        $ocupacion = DB::table('inscripciones')
            ->select('id_grupo')
            ->selectRaw('COUNT(*) AS total')
            ->groupBy('id_grupo');

        $this->gruposDisponibles = DB::table('grupos as g')
            ->join('materias as m', 'm.id_materia', '=', 'g.id_materia')
            ->join('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo')
            ->leftJoin('docentes as d', 'd.id_docente', '=', 'g.id_docente')
            ->leftJoinSub($ocupacion, 'ocupacion', function (JoinClause $join): void {
                $join->on('ocupacion.id_grupo', '=', 'g.id_grupo');
            })
            ->where('g.activo', true)
            ->where('g.id_periodo', (int) $periodo->id_periodo)
            ->where('m.activo', true)
            ->where('m.id_carrera', (int) $alumno->id_carrera)
            ->where('p.estado', 'Activo')
            ->where('p.activo', true)
            ->where(function (QueryBuilder $query): void {
                $query->whereNull('g.id_docente')
                    ->orWhere('d.activo', true);
            })
            ->select([
                'g.id_grupo',
                'g.nombre as grupo',
                'g.cupo_maximo',
                'g.id_docente',
                'm.clave as materia_clave',
                'm.nombre as materia',
                'd.nombre as docente_nombre',
                'd.apellido_paterno as docente_apellido_paterno',
                'd.apellido_materno as docente_apellido_materno',
                'p.clave as periodo_clave',
            ])
            ->selectRaw('COALESCE(ocupacion.total, 0) AS ocupados')
            ->selectRaw('CASE WHEN COALESCE(ocupacion.total, 0) < g.cupo_maximo THEN 1 ELSE 0 END AS disponible')
            ->orderBy('m.nombre')
            ->orderBy('g.nombre')
            ->get()
            ->map(static fn (object $grupo): array => (array) $grupo)
            ->all();
    }

    private function mostrarErrorDeRegistro(string $resultado): void
    {
        $errores = [
            'alumno_no_disponible' => ['alumnoId', 'El alumno ya no existe o está inactivo.'],
            'grupo_no_disponible' => ['grupoId', 'El grupo ya no existe o está inactivo.'],
            'materia_no_disponible' => ['grupoId', 'La materia del grupo ya no está activa.'],
            'periodo_no_disponible' => ['periodoId', 'El periodo del grupo ya no está Activo o no corresponde al seleccionado.'],
            'carrera_incompatible' => ['grupoId', 'La materia del grupo no pertenece a la carrera del alumno.'],
            'docente_no_disponible' => ['grupoId', 'El docente titular del grupo está inactivo.'],
            'duplicada' => ['grupoId', 'El alumno ya está inscrito en este grupo.'],
            'cupo_lleno' => ['grupoId', 'El grupo ya no tiene cupo disponible.'],
        ];

        [$campo, $mensaje] = $errores[$resultado] ?? [
            'grupoId',
            'No se pudo registrar la inscripción con la información seleccionada.',
        ];

        $this->addError($campo, $mensaje);
    }

    private function autorizar(string $accion): void
    {
        $usuario = Auth::user();
        $rol = mb_strtolower(trim((string) ($usuario?->role?->nombre ?? '')));
        $esControlEscolar = $rol === 'control escolar';

        abort_unless($usuario !== null && ($usuario->esAdministrador() || $esControlEscolar), 403);
        abort_unless(Gate::allows('Inscripciones.'.$accion), 403);
    }
}
