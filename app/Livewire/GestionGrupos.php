<?php

namespace App\Livewire;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\PeriodoEscolar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class GestionGrupos extends Component
{
    use WithPagination;

    public $search = '';

    public $filtroCarrera = '';

    public $filtroMateria = '';

    public $filtroPeriodo = '';

    public $filtroEstado = '';

    public $id_materia = '';

    public $id_docente = '';

    public $id_periodo = '';

    public $nombre = '';

    public $cupo_maximo = 30;

    public $editingGrupoId = null;

    public $showModal = false;

    public $grupoCount = 0;

    public $gruposActivosCount = 0;

    public $gruposInactivosCount = 0;

    public $cupoTotal = 0;

    public $avisoDocenteCarrera = null;

    public $gruposDuplicados = [];

    public $relacionesGrupoActuales = [];

    public $inscripcionesGrupoActual = 0;

    // Selects
    public $materias = [];

    public $docentes = [];

    public $periodos = [];

    public $carrerasFiltro = [];

    public $materiasFiltro = [];

    public $periodosFiltro = [];

    public function mount()
    {
        $this->cargarGrupos();
        $this->cargarSelects();
    }

    public function cargarSelects(?Grupo $grupoActual = null): void
    {
        $this->exigirPermiso('ver');

        $this->materias = Materia::with('carrera')
            ->where(function (Builder $query) use ($grupoActual): void {
                $query->where('activo', 1);

                if ($grupoActual !== null) {
                    $query->orWhere('id_materia', $grupoActual->id_materia);
                }
            })
            ->orderBy('nombre')
            ->get(['id_materia', 'id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();

        $this->docentes = Docente::query()
            ->where(function (Builder $query) use ($grupoActual): void {
                $query->where('activo', 1);

                if ($grupoActual?->id_docente !== null) {
                    $query->orWhere('id_docente', $grupoActual->id_docente);
                }
            })
            ->orderBy('apellido_paterno')
            ->get(['id_docente', 'nombre', 'apellido_paterno', 'apellido_materno', 'activo'])
            ->toArray();

        $this->periodos = PeriodoEscolar::query()
            ->where(function (Builder $query) use ($grupoActual): void {
                $query->where('estado', 'Próximo')
                    ->orWhere(function (Builder $activo): void {
                        $activo->where('estado', 'Activo')->where('activo', 1);
                    });

                if ($grupoActual !== null) {
                    $query->orWhere('id_periodo', $grupoActual->id_periodo);
                }
            })
            ->orderByDesc('clave')
            ->get(['id_periodo', 'clave', 'nombre', 'estado', 'activo'])
            ->toArray();

        $this->carrerasFiltro = Carrera::query()
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();

        $this->materiasFiltro = Materia::with('carrera')
            ->orderBy('nombre')
            ->get(['id_materia', 'id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();

        $this->periodosFiltro = PeriodoEscolar::query()
            ->orderByDesc('clave')
            ->get(['id_periodo', 'clave', 'nombre', 'estado', 'activo'])
            ->toArray();
    }

    public function cargarGrupos(): void
    {
        $this->exigirPermiso('ver');
        $this->grupoCount = Grupo::count();
        $this->gruposActivosCount = Grupo::where('activo', 1)->count();
        $this->gruposInactivosCount = Grupo::where('activo', 0)->count();
        $this->cupoTotal = (int) Grupo::sum('cupo_maximo');
    }

    public function updatedSearch(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage('gruposPage');
    }

    public function updatedFiltroCarrera(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage('gruposPage');
    }

    public function updatedFiltroMateria(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage('gruposPage');
    }

    public function updatedFiltroPeriodo(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage('gruposPage');
    }

    public function updatedFiltroEstado(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage('gruposPage');
    }

    public function updatedIdMateria(): void
    {
        $this->autorizarEdicionFormulario();
        $this->actualizarAvisosFormulario();
    }

    public function updatedIdDocente(): void
    {
        $this->autorizarEdicionFormulario();
        $this->actualizarAvisosFormulario();
    }

    public function updatedIdPeriodo(): void
    {
        $this->autorizarEdicionFormulario();
        $this->actualizarAvisosFormulario();
    }

    public function updatedNombre(): void
    {
        $this->autorizarEdicionFormulario();
        $this->actualizarAvisosFormulario();
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');
        $this->editingGrupoId = null;
        $this->reset(['id_materia', 'id_docente', 'id_periodo', 'nombre', 'cupo_maximo']);
        $this->cupo_maximo = 30;
        $this->avisoDocenteCarrera = null;
        $this->gruposDuplicados = [];
        $this->relacionesGrupoActuales = [];
        $this->inscripcionesGrupoActual = 0;
        $this->cargarSelects();
        $this->showModal = true;
    }

    public function abrirModalEditar($grupoId): void
    {
        $this->exigirPermiso('editar');
        $grupo = Grupo::with(['materia.carrera', 'docente', 'periodo'])->findOrFail($grupoId);

        $this->editingGrupoId = $grupoId;
        $this->id_materia = $grupo->id_materia;
        $this->id_docente = $grupo->id_docente;
        $this->id_periodo = $grupo->id_periodo;
        $this->nombre = $grupo->nombre;
        $this->cupo_maximo = $grupo->cupo_maximo;
        $this->relacionesGrupoActuales = $this->relacionesAcademicasDelGrupo((int) $grupo->id_grupo);
        $this->inscripcionesGrupoActual = DB::table('inscripciones')->where('id_grupo', $grupo->id_grupo)->count();
        $this->cargarSelects($grupo);
        $this->actualizarAvisosFormulario();
        $this->showModal = true;
    }

    private function autorizarEdicionFormulario(): void
    {
        $this->exigirPermiso($this->editingGrupoId === null ? 'crear' : 'editar');
    }

    /** Muestra avisos de pertenencia docente-carrera y posibles grupos repetidos. */
    private function actualizarAvisosFormulario(): void
    {
        $this->avisoDocenteCarrera = null;
        $this->gruposDuplicados = [];

        $materiaId = (int) $this->id_materia;
        $docenteId = (int) $this->id_docente;
        $nombreGrupo = trim((string) $this->nombre);

        if ($materiaId > 0 && $docenteId > 0) {
            $materia = Materia::with('carrera')->find($materiaId);

            if ($materia?->carrera !== null) {
                $perteneceFormalmente = DB::table('carrera_docente')
                    ->where('id_carrera', $materia->id_carrera)
                    ->where('id_docente', $docenteId)
                    ->exists();

                if (! $perteneceFormalmente) {
                    $this->avisoDocenteCarrera = 'El docente no figura como integrante formal de '
                        .$materia->carrera->nombre.'. La asignación titular se conserva como una relación distinta.';
                }
            }
        }

        if ($materiaId < 1 || (int) $this->id_periodo < 1 || $nombreGrupo === '') {
            return;
        }

        $this->gruposDuplicados = Grupo::query()
            ->with(['materia', 'periodo'])
            ->where('id_materia', $materiaId)
            ->where('id_periodo', (int) $this->id_periodo)
            ->where('nombre', $nombreGrupo)
            ->when($this->editingGrupoId !== null, function (Builder $query): void {
                $query->where('id_grupo', '<>', $this->editingGrupoId);
            })
            ->orderBy('id_grupo')
            ->limit(5)
            ->get(['id_grupo', 'id_materia', 'id_periodo', 'nombre', 'activo'])
            ->map(fn (Grupo $grupo) => [
                'id_grupo' => $grupo->id_grupo,
                'nombre' => $grupo->nombre,
                'materia' => $grupo->materia?->clave.' · '.$grupo->materia?->nombre,
                'periodo' => $grupo->periodo?->clave,
                'activo' => (bool) $grupo->activo,
            ])
            ->toArray();
    }

    /** Paginador con búsqueda y filtros independientes y combinables. */
    private function consultaGrupos(): Builder
    {
        $busqueda = trim((string) $this->search);

        return Grupo::query()
            ->with(['materia.carrera', 'docente', 'periodo'])
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $subquery) use ($busqueda): void {
                    $subquery->where('nombre', 'like', "%{$busqueda}%")
                        ->orWhereHas('materia', function (Builder $materiaQuery) use ($busqueda): void {
                            $materiaQuery->where('nombre', 'like', "%{$busqueda}%")
                                ->orWhere('clave', 'like', "%{$busqueda}%")
                                ->orWhereHas('carrera', function (Builder $carreraQuery) use ($busqueda): void {
                                    $carreraQuery->where('nombre', 'like', "%{$busqueda}%")
                                        ->orWhere('clave', 'like', "%{$busqueda}%");
                                });
                        })
                        ->orWhereHas('docente', function (Builder $docenteQuery) use ($busqueda): void {
                            $docenteQuery->where('no_empleado', 'like', "%{$busqueda}%")
                                ->orWhere('nombre', 'like', "%{$busqueda}%")
                                ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                                ->orWhere('apellido_materno', 'like', "%{$busqueda}%");
                        })
                        ->orWhereHas('periodo', function (Builder $periodoQuery) use ($busqueda): void {
                            $periodoQuery->where('clave', 'like', "%{$busqueda}%")
                                ->orWhere('nombre', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->when($this->filtroCarrera !== '', function (Builder $query): void {
                $query->whereHas('materia', fn (Builder $materiaQuery) => $materiaQuery
                    ->where('id_carrera', (int) $this->filtroCarrera));
            })
            ->when($this->filtroMateria !== '', function (Builder $query): void {
                $query->where('id_materia', (int) $this->filtroMateria);
            })
            ->when($this->filtroPeriodo !== '', function (Builder $query): void {
                $query->where('id_periodo', (int) $this->filtroPeriodo);
            })
            ->when(in_array((string) $this->filtroEstado, ['0', '1'], true), function (Builder $query): void {
                $query->where('activo', (int) $this->filtroEstado);
            })
            ->orderByDesc('id_periodo')
            ->orderBy('nombre')
            ->orderBy('id_grupo');
    }

    /** Cuentas directas e indirectas que hacen riesgoso borrar el grupo. */
    private function relacionesAcademicasDelGrupo(int $grupoId): array
    {
        $relaciones = [];

        if (DB::table('inscripciones')->where('id_grupo', $grupoId)->exists()) {
            $relaciones[] = 'inscripciones';
        }

        if (DB::table('calificaciones as c')
            ->join('inscripciones as i', 'i.id_inscripcion', '=', 'c.id_inscripcion')
            ->where('i.id_grupo', $grupoId)
            ->exists()) {
            $relaciones[] = 'calificaciones';
        }

        if (DB::table('actas')->where('id_grupo', $grupoId)->exists()) {
            $relaciones[] = 'actas';
        }

        if (DB::table('inscripcion_grupos')->where('id_grupo', $grupoId)->exists()) {
            $relaciones[] = 'relaciones de inscripción-grupo';
        }

        if (DB::table('asignaciones_docente')->where('id_grupo', $grupoId)->exists()) {
            $relaciones[] = 'asignaciones docentes';
        }

        return $relaciones;
    }

    /** Valida los catálogos elegidos y conserva referencias históricas sin cambio. */
    private function validarReferenciasSeleccionadas(?Grupo $grupo): bool
    {
        $materia = Materia::with('carrera')->find((int) $this->id_materia);

        if ($materia === null || $materia->carrera === null) {
            $this->addError('id_materia', 'La materia debe pertenecer a una carrera existente.');

            return false;
        }

        $materiaSinCambio = $grupo !== null && (int) $grupo->id_materia === (int) $materia->id_materia;

        if (! $materia->activo && ! $materiaSinCambio) {
            $this->addError('id_materia', 'No se puede asignar una materia inactiva a un grupo nuevo o diferente.');

            return false;
        }

        $periodo = PeriodoEscolar::find((int) $this->id_periodo);

        if ($periodo === null) {
            $this->addError('id_periodo', 'El periodo escolar seleccionado no existe.');

            return false;
        }

        $periodoSinCambio = $grupo !== null && (int) $grupo->id_periodo === (int) $periodo->id_periodo;

        $periodoHabilitado = $periodo->estado === 'Próximo'
            || ($periodo->estado === 'Activo' && (bool) $periodo->activo);

        if (! $periodoHabilitado && ! $periodoSinCambio) {
            $this->addError('id_periodo', 'Selecciona un periodo Próximo o Activo. Se conserva el periodo histórico actual si no se cambia.');

            return false;
        }

        if ($this->id_docente !== '' && $this->id_docente !== null) {
            $docente = Docente::find((int) $this->id_docente);

            if ($docente === null) {
                $this->addError('id_docente', 'El docente seleccionado no existe.');

                return false;
            }

            $docenteSinCambio = $grupo !== null
                && $grupo->id_docente !== null
                && (int) $grupo->id_docente === (int) $docente->id_docente;

            if (! $docente->activo && ! $docenteSinCambio) {
                $this->addError('id_docente', 'No se puede asignar un docente inactivo a un grupo nuevo o diferente.');

                return false;
            }
        }

        return true;
    }

    public function guardar(): void
    {
        $esEdicion = $this->editingGrupoId !== null;
        $this->exigirPermiso($esEdicion ? 'editar' : 'crear');

        $grupoOriginal = $esEdicion ? Grupo::findOrFail($this->editingGrupoId) : null;
        $idMateria = (int) $this->id_materia;
        $idPeriodo = (int) $this->id_periodo;

        $this->validate([
            'id_materia' => ['required', 'integer', Rule::exists('materias', 'id_materia')],
            'id_docente' => ['nullable', 'integer', Rule::exists('docentes', 'id_docente')],
            'id_periodo' => ['required', 'integer', Rule::exists('periodo_escolars', 'id_periodo')],
            'nombre' => ['required', 'string', 'min:1', 'max:20'],
            'cupo_maximo' => ['required', 'integer', 'min:1', 'max:100'],
        ], [
            'id_materia.required' => 'Debes seleccionar una materia activa.',
            'id_materia.exists' => 'La materia seleccionada no existe.',
            'id_docente.exists' => 'El docente seleccionado no existe.',
            'id_periodo.required' => 'Debes seleccionar un periodo escolar habilitado.',
            'id_periodo.exists' => 'El periodo seleccionado no existe.',
            'nombre.required' => 'El nombre del grupo es obligatorio.',
            'nombre.max' => 'El nombre del grupo no puede superar los 20 caracteres.',
            'cupo_maximo.required' => 'El cupo máximo es obligatorio.',
            'cupo_maximo.integer' => 'El cupo máximo debe ser un número entero.',
            'cupo_maximo.min' => 'El cupo máximo debe ser al menos 1.',
            'cupo_maximo.max' => 'El cupo máximo no puede superar los 100 alumnos.',
        ]);

        if (! $this->validarReferenciasSeleccionadas($grupoOriginal)) {
            return;
        }

        $cupo = (int) $this->cupo_maximo;
        $data = [
            'id_materia' => $idMateria,
            'id_docente' => $this->id_docente ? (int) $this->id_docente : null,
            'id_periodo' => $idPeriodo,
            'nombre' => trim((string) $this->nombre),
            'cupo_maximo' => $cupo,
        ];

        $this->actualizarAvisosFormulario();
        $coincidencias = $this->gruposDuplicados;

        try {
            $guardado = DB::transaction(function () use ($data, $esEdicion): bool {
                $grupo = $esEdicion
                    ? Grupo::query()->whereKey($this->editingGrupoId)->lockForUpdate()->firstOrFail()
                    : null;

                Materia::query()->whereKey($data['id_materia'])->lockForUpdate()->first();
                PeriodoEscolar::query()->whereKey($data['id_periodo'])->lockForUpdate()->first();

                if ($data['id_docente'] !== null) {
                    Docente::query()->whereKey($data['id_docente'])->lockForUpdate()->first();
                }

                if (! $this->validarReferenciasSeleccionadas($grupo)) {
                    return false;
                }

                if ($grupo !== null) {
                    $relaciones = $this->relacionesAcademicasDelGrupo((int) $grupo->id_grupo);
                    $camposClaveCambiaron = (int) $grupo->id_materia !== (int) $data['id_materia']
                        || (int) $grupo->id_periodo !== (int) $data['id_periodo'];

                    if ($relaciones !== [] && $camposClaveCambiaron) {
                        $this->addError(
                            'id_materia',
                            'El grupo tiene información académica relacionada ('.implode(', ', $relaciones).'). No se puede cambiar su materia o periodo.'
                        );

                        return false;
                    }

                    $inscripciones = DB::table('inscripciones')
                        ->where('id_grupo', $grupo->id_grupo)
                        ->count();

                    if ((int) $data['cupo_maximo'] < $inscripciones) {
                        $this->addError(
                            'cupo_maximo',
                            "El cupo no puede ser menor que las {$inscripciones} inscripciones ya registradas."
                        );

                        return false;
                    }

                    $grupo->update($data);
                } else {
                    Grupo::create($data + ['activo' => 1]);
                }

                return true;
            });
        } catch (QueryException $e) {
            report($e);
            $this->addError('id_materia', 'No se pudo guardar el grupo por una restricción de integridad. No se aplicaron cambios parciales.');

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('nombre', 'No se pudo guardar el grupo. La operación se revirtió sin cambios parciales.');

            return;
        }

        if (! $guardado) {
            return;
        }

        $esEdicionExitoso = $esEdicion;
        $this->showModal = false;
        $this->reset(['id_materia', 'id_docente', 'id_periodo', 'nombre', 'cupo_maximo']);
        $this->cupo_maximo = 30;
        $this->editingGrupoId = null;
        $this->avisoDocenteCarrera = null;
        $this->gruposDuplicados = [];
        $this->relacionesGrupoActuales = [];
        $this->inscripcionesGrupoActual = 0;
        $this->resetPage('gruposPage');
        $this->cargarSelects();
        $this->cargarGrupos();

        session()->flash('success', $esEdicionExitoso
            ? 'Grupo actualizado correctamente.'
            : 'Grupo registrado exitosamente.');

        if ($coincidencias !== []) {
            session()->flash(
                'warning',
                'Se guardó el grupo, pero existe otra coincidencia para la misma materia, periodo y nombre. Revisa el catálogo antes de crear otro igual.'
            );
        }
    }

    public function eliminar($grupoId): void
    {
        $this->exigirPermiso('eliminar');

        $relaciones = [];
        $eliminado = false;

        try {
            DB::transaction(function () use ($grupoId, &$relaciones, &$eliminado): void {
                $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();
                $relaciones = $this->relacionesAcademicasDelGrupo((int) $grupo->id_grupo);

                if ($relaciones !== []) {
                    if ($grupo->activo) {
                        $grupo->activo = false;
                        $grupo->save();
                    }

                    return;
                }

                $grupo->delete();
                $eliminado = true;
            });
        } catch (QueryException $e) {
            if ($e->getCode() === '23000') {
                $grupo = Grupo::findOrFail($grupoId);
                $relaciones = $this->relacionesAcademicasDelGrupo((int) $grupo->id_grupo);

                if ($grupo->activo) {
                    $grupo->activo = false;
                    $grupo->save();
                }

                session()->flash(
                    'error',
                    'La base de datos protegió las relaciones del grupo. Se conservó el registro y se desactivó.'
                );
            } else {
                report($e);
                session()->flash('error', 'No se pudo eliminar el grupo. No se aplicaron cambios parciales.');
            }

            $this->resetPage('gruposPage');
            $this->cargarGrupos();

            return;
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'No se pudo procesar el grupo. La operación se revirtió sin cambios parciales.');

            return;
        }

        if ($relaciones !== []) {
            session()->flash(
                'error',
                'El grupo tiene información relacionada: '.implode(', ', $relaciones).'. '
                .'Se conservó el grupo y se desactivó para proteger los registros académicos.'
            );
        } elseif ($eliminado) {
            session()->flash('success', 'Grupo eliminado correctamente.');
        }

        $this->resetPage('gruposPage');
        $this->cargarGrupos();
    }

    public function toggleActivo($grupoId): void
    {
        $grupo = Grupo::findOrFail($grupoId);
        $this->exigirPermiso($grupo->activo ? 'eliminar' : 'editar');

        $grupo->activo = ! $grupo->activo;
        $grupo->save();

        $this->resetPage('gruposPage');
        $this->cargarGrupos();
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        $grupos = $this->consultaGrupos()->paginate(15, ['*'], 'gruposPage');
        $usuarioActual = Auth::user();

        return view('admin.grupos.index', [
            'grupos' => $grupos,
            'gruposFiltradosCount' => $grupos->total(),
            'puedeCrear' => $usuarioActual?->tienePermiso('Grupos y Asignaciones', 'crear') ?? false,
            'puedeEditar' => $usuarioActual?->tienePermiso('Grupos y Asignaciones', 'editar') ?? false,
            'puedeEliminar' => $usuarioActual?->tienePermiso('Grupos y Asignaciones', 'eliminar') ?? false,
        ]);
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::allows('Grupos y Asignaciones.'.$accion), 403);
    }
}
