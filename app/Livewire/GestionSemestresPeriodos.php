<?php

namespace App\Livewire;

use App\Models\Carrera;
use App\Models\PeriodoEscolar;
use App\Models\Semestre;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class GestionSemestresPeriodos extends Component
{
    // Pestaña activa: 'semestres' | 'periodos'
    public $tab = 'semestres';

    // ---- Semestres ----
    public $semestres = [];

    public $semestreSearch = '';

    public $id_carrera = '';

    public $numero = '';

    public $descripcion = '';

    public $editingSemestreId = null;

    public $showModalSemestre = false;

    public $semestreCount = 0;

    // ---- Periodos Escolares ----
    public $periodos = [];

    public $periodoSearch = '';

    public $clave = '';

    public $nombre = '';

    public $fecha_inicio = '';

    public $fecha_fin = '';

    // Estado del periodo: 'Próximo' | 'Activo' | 'Finalizado'
    public $estado = PeriodoEscolar::ESTADO_DEFECTO;

    public $editingPeriodoId = null;

    public $showModalPeriodo = false;

    public $periodoCount = 0;

    /**
     * Confirmación generada por el servidor para una activación de periodo.
     * Los datos quedan bloqueados para que el cliente no fabrique el conflicto
     * que debe confirmar.
     *
     * @var array{
     *     accion: 'crear'|'editar',
     *     periodo_objetivo_id: int|null,
     *     periodo_objetivo_original: array<string, int|string|bool|null>|null,
     *     datos: array{clave: string, nombre: string, fecha_inicio: string|null, fecha_fin: string|null, estado: string},
     *     periodo_activo_id: int,
     *     periodo_activo_clave: string,
     *     periodo_activo_original: array<string, int|string|bool|null>,
     *     periodo_activo_actualizado: string|null
     * }|null
     */
    #[Locked]
    public ?array $confirmacionCambioActivoPendiente = null;

    // Selects
    public $carreras = [];

    public function mount()
    {
        $this->exigirPermiso('Carreras y Semestres', 'ver');
        $this->exigirPermiso('Periodos Escolares', 'ver');

        $this->cargarSemestres();
        $this->cargarPeriodos();
        $this->cargarSelects();
    }

    public function cargarSelects()
    {
        $this->exigirPermiso('Carreras y Semestres', 'ver');

        $this->carreras = Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();
    }

    /* ============================================================
       PESTAÑAS
    ============================================================ */
    public function switchTab($tab)
    {
        $this->tab = $tab;
    }

    /* ============================================================
       SEMESTRES
    ============================================================ */
    public function cargarSemestres()
    {
        $this->exigirPermiso('Carreras y Semestres', 'ver');

        $query = Semestre::with('carrera');

        if ($this->semestreSearch) {
            $search = $this->semestreSearch;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%")
                    ->orWhereHas('carrera', function ($qc) use ($search) {
                        $qc->where('nombre', 'like', "%{$search}%")
                            ->orWhere('clave', 'like', "%{$search}%");
                    });
            });
        }

        $this->semestres = $query->get()->toArray();
        $this->semestreCount = count($this->semestres);
    }

    public function updatedSemestreSearch()
    {
        $this->cargarSemestres();
    }

    public function abrirModalSemestreCrear()
    {
        $this->exigirPermiso('Carreras y Semestres', 'crear');

        $this->editingSemestreId = null;
        $this->reset(['id_carrera', 'numero', 'descripcion']);
        $this->showModalSemestre = true;
    }

    public function abrirModalSemestreEditar($semestreId)
    {
        $this->exigirPermiso('Carreras y Semestres', 'editar');

        $semestre = Semestre::findOrFail($semestreId);

        $this->editingSemestreId = $semestreId;
        $this->id_carrera = $semestre->id_carrera;
        $this->numero = $semestre->numero;
        $this->descripcion = $semestre->descripcion;
        $this->showModalSemestre = true;
    }

    public function guardarSemestre()
    {
        $esEdicion = (bool) $this->editingSemestreId;
        $this->exigirPermiso('Carreras y Semestres', $esEdicion ? 'editar' : 'crear');

        $this->validate([
            'id_carrera' => 'required|exists:carreras,id_carrera',
            'numero' => 'required|integer|min:1|max:9',
            'descripcion' => 'nullable|string|max:60',
        ]);

        try {
            DB::transaction(function () use ($esEdicion): void {
                $this->exigirPermiso('Carreras y Semestres', $esEdicion ? 'editar' : 'crear');

                $datos = [
                    'id_carrera' => $this->id_carrera,
                    'numero' => $this->numero,
                    'descripcion' => $this->descripcion,
                ];

                if ($esEdicion) {
                    $semestre = Semestre::query()
                        ->whereKey($this->editingSemestreId)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $semestre->update($datos);
                } else {
                    Semestre::create($datos);
                }
            });
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'No se pudo guardar el semestre. No se aplicaron cambios parciales.');

            return;
        }

        $isEditing = $this->editingSemestreId;

        $this->showModalSemestre = false;
        $this->reset(['id_carrera', 'numero', 'descripcion']);
        $this->editingSemestreId = null;
        $this->cargarSemestres();

        session()->flash('success', $isEditing
            ? 'Semestre actualizado correctamente.'
            : 'Semestre registrado exitosamente.');
    }

    public function eliminarSemestre($semestreId)
    {
        $this->exigirPermiso('Carreras y Semestres', 'eliminar');

        try {
            DB::transaction(function () use ($semestreId): void {
                $semestre = Semestre::query()
                    ->whereKey($semestreId)
                    ->lockForUpdate()
                    ->firstOrFail();

                $semestre->delete();
            });
        } catch (QueryException $exception) {
            report($exception);
            session()->flash('error', 'No se pudo eliminar el semestre por una restricción de integridad. No se modificó ningún dato.');

            return;
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash('error', 'No se pudo eliminar el semestre. No se modificó ningún dato.');

            return;
        }

        $this->cargarSemestres();

        session()->flash('success', 'Semestre eliminado correctamente.');
    }

    /* ============================================================
       PERIODOS ESCOLARES
    ============================================================ */
    public function cargarPeriodos()
    {
        $this->exigirPermiso('Periodos Escolares', 'ver');

        $query = PeriodoEscolar::query();

        if ($this->periodoSearch) {
            $query->where(function ($q) {
                $q->where('clave', 'like', "%{$this->periodoSearch}%")
                    ->orWhere('nombre', 'like', "%{$this->periodoSearch}%");
            });
        }

        $this->periodos = $query->get()->toArray();
        $this->periodoCount = count($this->periodos);
    }

    public function updatedPeriodoSearch()
    {
        $this->cargarPeriodos();
    }

    public function abrirModalPeriodoCrear()
    {
        $this->exigirPermiso('Periodos Escolares', 'crear');

        $this->editingPeriodoId = null;
        $this->reset(['clave', 'nombre', 'fecha_inicio', 'fecha_fin']);
        $this->estado = PeriodoEscolar::ESTADO_DEFECTO;
        $this->confirmacionCambioActivoPendiente = null;
        $this->showModalPeriodo = true;
    }

    public function abrirModalPeriodoEditar($periodoId)
    {
        $this->exigirPermiso('Periodos Escolares', 'editar');

        $periodo = PeriodoEscolar::findOrFail($periodoId);

        $this->editingPeriodoId = $periodoId;
        $this->clave = $periodo->clave;
        $this->nombre = $periodo->nombre;
        $this->fecha_inicio = $periodo->fecha_inicio;
        $this->fecha_fin = $periodo->fecha_fin;
        $this->estado = $periodo->estado ?? PeriodoEscolar::ESTADO_DEFECTO;
        $this->confirmacionCambioActivoPendiente = null;
        $this->showModalPeriodo = true;
    }

    /**
     * Si el usuario cambia el estado a algo distinto de 'Activo', la
     * confirmacion de cambio de periodo activo deja de tener sentido.
     */
    public function updatedEstado()
    {
        if ($this->estado !== 'Activo' && $this->confirmacionCambioActivoPendiente !== null) {
            $this->confirmacionCambioActivoPendiente = null;
        }
    }

    /**
     * Acceso rapido desde el Dashboard: cambia a la pestaña "Periodos" y
     * abre el formulario de nuevo periodo.
     */
    #[On('abrir-nuevo-periodo')]
    public function abrirNuevoPeriodoDesdeDashboard()
    {
        $this->abrirModalPeriodoCrear();
        $this->switchTab('periodos');
    }

    public function cerrarModalPeriodo(): void
    {
        $this->showModalPeriodo = false;
        $this->confirmacionCambioActivoPendiente = null;
    }

    public function guardarPeriodo(): void
    {
        $periodoId = $this->editingPeriodoId === null ? null : (int) $this->editingPeriodoId;
        $accion = $periodoId === null ? 'crear' : 'editar';
        $this->exigirPermiso('Periodos Escolares', $accion);

        if ($this->confirmacionCambioActivoPendiente !== null) {
            $this->addError('estado', 'Confirma o cancela el cambio del periodo activo antes de volver a guardar.');

            return;
        }

        $datos = $this->validarDatosPeriodo($periodoId);
        $resultado = $this->guardarPeriodoEnTransaccion($periodoId, $datos);

        if ($resultado === null) {
            return;
        }

        if ($resultado['estado'] === 'confirmacion') {
            $this->confirmacionCambioActivoPendiente = $resultado['pendiente'];

            return;
        }

        $this->finalizarGuardadoPeriodo($periodoId !== null);
    }

    public function confirmarCambioActivo(): void
    {
        $pendiente = $this->confirmacionCambioActivoPendiente;
        $accion = $pendiente['accion']
            ?? ($this->editingPeriodoId === null ? 'crear' : 'editar');
        $this->exigirPermiso('Periodos Escolares', $accion);

        if ($pendiente === null) {
            $this->addError('estado', 'No hay un cambio de periodo activo pendiente de confirmación.');

            return;
        }

        $periodoId = $this->editingPeriodoId === null ? null : (int) $this->editingPeriodoId;

        if ($periodoId !== $pendiente['periodo_objetivo_id']) {
            $this->confirmacionCambioActivoPendiente = null;
            $this->addError('estado', 'El periodo seleccionado cambió. Revisa el formulario y solicita la confirmación nuevamente.');

            return;
        }

        try {
            $datos = $this->validarDatosPeriodo($periodoId);
        } catch (ValidationException $exception) {
            $this->confirmacionCambioActivoPendiente = null;

            throw $exception;
        }

        if ($datos !== $pendiente['datos']) {
            $this->confirmacionCambioActivoPendiente = null;
            $this->addError('estado', 'Los datos del formulario cambiaron después de mostrar la confirmación. Revísalos y vuelve a guardar.');

            return;
        }

        $resultado = $this->guardarPeriodoEnTransaccion($periodoId, $datos, $pendiente);

        if ($resultado === null || $resultado['estado'] !== 'guardado') {
            $this->confirmacionCambioActivoPendiente = null;

            return;
        }

        $this->finalizarGuardadoPeriodo($periodoId !== null);
    }

    public function cancelarCambioActivo(): void
    {
        $pendiente = $this->confirmacionCambioActivoPendiente;

        if ($pendiente === null) {
            return;
        }

        $this->estado = $pendiente['periodo_objetivo_original']['estado'] ?? PeriodoEscolar::ESTADO_DEFECTO;
        $this->confirmacionCambioActivoPendiente = null;
        $this->resetValidation('estado');
    }

    public function eliminarPeriodo(int $periodoId): void
    {
        $this->exigirPermiso('Periodos Escolares', 'eliminar');

        session()->flash(
            'error',
            'La eliminación de periodos está temporalmente suspendida para proteger grupos, inscripciones y registros académicos.'
        );
    }

    /** @return array{clave: string, nombre: string, fecha_inicio: string|null, fecha_fin: string|null, estado: string} */
    private function validarDatosPeriodo(?int $periodoId): array
    {
        $reglaClave = Rule::unique('periodo_escolars', 'clave');

        if ($periodoId !== null) {
            $reglaClave->ignore($periodoId, 'id_periodo');
        }

        $this->validate([
            'clave' => ['required', 'string', 'min:2', 'max:20', $reglaClave],
            'nombre' => ['required', 'string', 'min:3', 'max:50'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'estado' => ['required', Rule::in(PeriodoEscolar::ESTADOS)],
        ]);

        return [
            'clave' => (string) $this->clave,
            'nombre' => (string) $this->nombre,
            'fecha_inicio' => $this->fecha_inicio === '' || $this->fecha_inicio === null
                ? null
                : (string) $this->fecha_inicio,
            'fecha_fin' => $this->fecha_fin === '' || $this->fecha_fin === null
                ? null
                : (string) $this->fecha_fin,
            'estado' => (string) $this->estado,
        ];
    }

    /**
     * Revalida el estado de los periodos dentro de la transacción. Se bloquean
     * las filas existentes para serializar los cambios hechos desde este módulo.
     *
     * @param  array{clave: string, nombre: string, fecha_inicio: string|null, fecha_fin: string|null, estado: string}  $datos
     * @param array{
     *     accion: 'crear'|'editar',
     *     periodo_objetivo_id: int|null,
     *     periodo_objetivo_original: array<string, int|string|bool|null>|null,
     *     datos: array{clave: string, nombre: string, fecha_inicio: string|null, fecha_fin: string|null, estado: string},
     *     periodo_activo_id: int,
     *     periodo_activo_clave: string,
     *     periodo_activo_original: array<string, int|string|bool|null>,
     *     periodo_activo_actualizado: string|null
     * }|null $confirmacion
     * @return array{estado: 'guardado'}|array{estado: 'confirmacion', pendiente: array<string, mixed>}|null
     */
    private function guardarPeriodoEnTransaccion(?int $periodoId, array $datos, ?array $confirmacion = null): ?array
    {
        try {
            return DB::transaction(function () use ($periodoId, $datos, $confirmacion): ?array {
                $accion = $periodoId === null ? 'crear' : 'editar';
                $this->exigirPermiso('Periodos Escolares', $accion);

                // Lock de las filas existentes para volver a evaluar los conflictos
                // con el mismo conjunto de periodos antes de efectuar la escritura.
                $periodos = PeriodoEscolar::query()
                    ->orderBy('id_periodo')
                    ->lockForUpdate()
                    ->get();

                $periodoObjetivo = $periodoId === null
                    ? null
                    : $periodos->first(fn (PeriodoEscolar $periodo): bool => (int) $periodo->id_periodo === $periodoId);

                if ($periodoId !== null && $periodoObjetivo === null) {
                    $this->addError('clave', 'El periodo que intentas editar ya no existe.');

                    return null;
                }

                $problemaEstado = $this->problemaCoherenciaPeriodos($periodos, $periodoId);

                if ($problemaEstado !== null) {
                    $this->addError('estado', $problemaEstado);

                    return null;
                }

                if ($confirmacion !== null) {
                    if ($confirmacion['accion'] !== $accion
                        || $confirmacion['periodo_objetivo_id'] !== $periodoId
                        || $confirmacion['datos'] !== $datos
                        || $this->datosPersistidosPeriodo($periodoObjetivo) !== $confirmacion['periodo_objetivo_original']) {
                        $this->addError('estado', 'El periodo o sus datos cambiaron desde que se mostró la confirmación. Revisa el formulario y confirma nuevamente.');

                        return null;
                    }
                }

                $periodosActivos = $periodos
                    ->filter(fn (PeriodoEscolar $periodo): bool => (bool) $periodo->activo)
                    ->values();
                $otrosPeriodosActivos = $periodosActivos
                    ->reject(fn (PeriodoEscolar $periodo): bool => $periodoId !== null
                        && (int) $periodo->id_periodo === $periodoId)
                    ->values();

                if ($datos['estado'] === 'Activo') {
                    if ($otrosPeriodosActivos->count() > 1) {
                        $this->addError('estado', 'Hay varios periodos activos. Revisa el catálogo antes de activar otro periodo.');

                        return null;
                    }

                    if ($confirmacion === null && $otrosPeriodosActivos->isNotEmpty()) {
                        $periodoActivo = $otrosPeriodosActivos->first();

                        return [
                            'estado' => 'confirmacion',
                            'pendiente' => [
                                'accion' => $accion,
                                'periodo_objetivo_id' => $periodoId,
                                'periodo_objetivo_original' => $this->datosPersistidosPeriodo($periodoObjetivo),
                                'datos' => $datos,
                                'periodo_activo_id' => (int) $periodoActivo->id_periodo,
                                'periodo_activo_clave' => (string) $periodoActivo->clave,
                                'periodo_activo_original' => $this->datosPersistidosPeriodo($periodoActivo),
                                'periodo_activo_actualizado' => $periodoActivo->updated_at?->toDateTimeString(),
                            ],
                        ];
                    }

                    if ($confirmacion !== null) {
                        if ($otrosPeriodosActivos->count() !== 1) {
                            $this->addError('estado', 'El periodo activo cambió desde que se mostró la confirmación. Solicita una nueva confirmación.');

                            return null;
                        }

                        $periodoActivo = $otrosPeriodosActivos->first();

                        if ((int) $periodoActivo->id_periodo !== $confirmacion['periodo_activo_id']
                            || (string) $periodoActivo->clave !== $confirmacion['periodo_activo_clave']
                            || $this->datosPersistidosPeriodo($periodoActivo) !== $confirmacion['periodo_activo_original']
                            || $periodoActivo->updated_at?->toDateTimeString() !== $confirmacion['periodo_activo_actualizado']) {
                            $this->addError('estado', 'El periodo activo cambió desde que se mostró la confirmación. Solicita una nueva confirmación.');

                            return null;
                        }

                        $periodoActivo->update([
                            'estado' => 'Finalizado',
                            'activo' => 0,
                        ]);
                    }
                } elseif ($confirmacion !== null) {
                    $this->addError('estado', 'El estado seleccionado ya no requiere esta confirmación. Revisa el formulario.');

                    return null;
                } elseif ($otrosPeriodosActivos->count() > 1) {
                    $this->addError('estado', 'Hay varios periodos activos. Corrige esa inconsistencia antes de guardar cambios.');

                    return null;
                }

                $datos['activo'] = $datos['estado'] === 'Activo' ? 1 : 0;

                if ($periodoObjetivo !== null) {
                    $periodoObjetivo->update($datos);
                } else {
                    PeriodoEscolar::create($datos);
                }

                return ['estado' => 'guardado'];
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('estado', 'No se pudo guardar el periodo. La operación se revirtió sin cambios parciales.');

            return null;
        }
    }

    /** @return array<string, int|string|bool|null>|null */
    private function datosPersistidosPeriodo(?PeriodoEscolar $periodo): ?array
    {
        if ($periodo === null) {
            return null;
        }

        return [
            'clave' => (string) $periodo->clave,
            'nombre' => (string) $periodo->nombre,
            'fecha_inicio' => $periodo->fecha_inicio === null ? null : (string) $periodo->fecha_inicio,
            'fecha_fin' => $periodo->fecha_fin === null ? null : (string) $periodo->fecha_fin,
            'estado' => (string) $periodo->estado,
            'activo' => (bool) $periodo->activo,
            'updated_at' => $periodo->updated_at?->toDateTimeString(),
        ];
    }

    /**
     * @param  iterable<PeriodoEscolar>  $periodos
     */
    private function problemaCoherenciaPeriodos(iterable $periodos, ?int $periodoObjetivoId): ?string
    {
        foreach ($periodos as $periodo) {
            if ($periodoObjetivoId !== null && (int) $periodo->id_periodo === $periodoObjetivoId) {
                continue;
            }

            $activo = (bool) $periodo->activo;
            $estadoEsActivo = $periodo->estado === 'Activo';

            if (! in_array($periodo->estado, PeriodoEscolar::ESTADOS, true)
                && ($activo || $estadoEsActivo)) {
                return 'Existe un periodo con un estado activo desconocido. Revisa el catálogo antes de continuar.';
            }

            if ($activo !== $estadoEsActivo && ($activo || $estadoEsActivo)) {
                return 'El estado y el indicador activo de otro periodo no coinciden. Revisa el catálogo antes de continuar.';
            }
        }

        return null;
    }

    private function finalizarGuardadoPeriodo(bool $esEdicion): void
    {
        $this->showModalPeriodo = false;
        $this->reset(['clave', 'nombre', 'fecha_inicio', 'fecha_fin']);
        $this->estado = PeriodoEscolar::ESTADO_DEFECTO;
        $this->editingPeriodoId = null;
        $this->confirmacionCambioActivoPendiente = null;
        $this->cargarPeriodos();

        session()->flash('success', $esEdicion
            ? 'Periodo actualizado correctamente.'
            : 'Periodo registrado exitosamente.');
    }

    public function render()
    {
        $this->exigirPermiso('Carreras y Semestres', 'ver');
        $this->exigirPermiso('Periodos Escolares', 'ver');

        return view('admin.semestres-periodos.index', [
            'puedeCrearSemestre' => Gate::allows('Carreras y Semestres.crear'),
            'puedeEditarSemestre' => Gate::allows('Carreras y Semestres.editar'),
            'puedeEliminarSemestre' => Gate::allows('Carreras y Semestres.eliminar'),
            'puedeCrearPeriodo' => Gate::allows('Periodos Escolares.crear'),
            'puedeEditarPeriodo' => Gate::allows('Periodos Escolares.editar'),
        ]);
    }

    private function exigirPermiso(string $modulo, string $accion): void
    {
        abort_unless(
            Gate::allows($modulo.'.'.$accion),
            403,
            'No tienes permiso para realizar esta acción.'
        );
    }
}
