<?php

namespace App\Livewire;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\JefeCarrera;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class GestionDocentes extends Component
{
    public $docentes = [];

    public $search = '';

    public $nombre = '';

    public $apellido_paterno = '';

    public $apellido_materno = '';

    public $no_empleado = '';

    public $email = '';

    /** Catálogo de carreras activas (checkboxes del modal). */
    public $carreras = [];

    /**
     * IDs de carreras seleccionadas para el docente (N:N).
     * Puede llevar 1 o más elementos: un docente nuevo exige al menos una.
     */
    public $carreras_ids = [];

    #[Locked]
    public $editingDocenteId = null;

    #[Locked]
    public array $carrerasOriginalesConfirmacion = [];

    #[Locked]
    public array $carrerasRetiradasConfirmacion = [];

    #[Locked]
    public array $impactosAcademicosConfirmacion = [];

    #[Locked]
    public bool $confirmacionRetiroCarreras = false;

    public $showModal = false;

    public $docenteCount = 0;

    public $materiasAsignadasPeriodoActivo = 0;

    public function mount(): void
    {
        $this->exigirPermiso('ver');
        $this->cargarSelects();
        $this->cargarDocentes();
    }

    public function cargarSelects(?Docente $docenteActual = null): void
    {
        $this->exigirPermiso('ver');

        $idsCarrerasActuales = $docenteActual?->carreras
            ->pluck('id_carrera')
            ->map(fn ($id) => (int) $id)
            ->all() ?? [];

        $this->carreras = Carrera::query()
            ->where(function ($query) use ($idsCarrerasActuales): void {
                $query->where('activo', 1);

                if ($idsCarrerasActuales !== []) {
                    $query->orWhereIn('id_carrera', $idsCarrerasActuales);
                }
            })
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();
    }

    public function cargarDocentes(): void
    {
        $this->exigirPermiso('ver');

        // with('carreras') -> eager load del N:N: trae todas las carreras de
        // cada docente en UNA consulta extra, sin generar filas repetidas.
        $query = Docente::with('carreras');

        if ($this->search) {
            $search = $this->search;

            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('no_empleado', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                  // Buscar por nombre o clave de la carrera a la que PERTENECE.
                    ->orWhereHas('carreras', function ($qc) use ($search) {
                        $qc->where('nombre', 'like', "%{$search}%")
                            ->orWhere('clave', 'like', "%{$search}%");
                    });
            });

            // Garantía explícita contra duplicados: aunque la consulta crezca,
            // un docente que coincida en varias carreras aparece UNA sola vez.
            $query->distinct();
        }

        $this->docentes = $query->get()->toArray();
        $this->docenteCount = count($this->docentes);
        $this->materiasAsignadasPeriodoActivo = (int) DB::table('grupos as grupo')
            ->join('materias as materia', 'materia.id_materia', '=', 'grupo.id_materia')
            ->join('periodo_escolars as periodo', 'periodo.id_periodo', '=', 'grupo.id_periodo')
            ->where('grupo.activo', 1)
            ->whereNotNull('grupo.id_docente')
            ->where('periodo.activo', 1)
            ->distinct()
            ->count('grupo.id_materia');
    }

    public function updatedSearch(): void
    {
        $this->exigirPermiso('ver');
        $this->cargarDocentes();
    }

    public function updatedCarrerasIds(): void
    {
        $this->exigirPermiso($this->editingDocenteId !== null ? 'editar' : 'crear');
        $this->limpiarConfirmacionRetiroCarreras();
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');
        $this->limpiarConfirmacionRetiroCarreras();
        $this->editingDocenteId = null;
        $this->reset([
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'no_empleado',
            'email',
            'carreras_ids',
        ]);
        $this->cargarSelects();
        $this->showModal = true;
    }

    public function abrirModalEditar(int $docenteId): void
    {
        $this->exigirPermiso('editar');
        $docente = Docente::with('carreras')->findOrFail($docenteId);

        $this->editingDocenteId = $docenteId;
        $this->nombre = $docente->nombre;
        $this->apellido_paterno = $docente->apellido_paterno;
        $this->apellido_materno = $docente->apellido_materno;
        $this->no_empleado = $docente->no_empleado;
        $this->email = $docente->email;

        // Las asignaciones actuales se cargan en el formulario para que el
        // administrador vea exactamente qué se va a agregar o quitar.
        $this->carreras_ids = $this->normalizarIdsCarreras($docente->carreras->pluck('id_carrera')->all());

        $this->limpiarConfirmacionRetiroCarreras();
        $this->cargarSelects($docente);
        $this->showModal = true;
    }

    public function confirmarRetiroCarreras(): void
    {
        $this->exigirPermiso('editar');

        if ($this->carrerasRetiradasConfirmacion === []) {
            $this->addError('carreras_ids', 'No hay retiros de carrera pendientes de confirmación.');

            return;
        }

        $this->confirmacionRetiroCarreras = true;
        $this->guardar();
    }

    public function cancelarRetiroCarreras(): void
    {
        $this->exigirPermiso('editar');

        $this->carreras_ids = $this->normalizarIdsCarreras([
            ...$this->normalizarIdsCarreras((array) $this->carreras_ids),
            ...$this->carrerasRetiradasConfirmacion,
        ]);

        $this->limpiarConfirmacionRetiroCarreras();
    }

    /** @param array<int, int|string> $ids */
    private function normalizarIdsCarreras(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        sort($ids);

        return $ids;
    }

    private function limpiarConfirmacionRetiroCarreras(): void
    {
        $this->carrerasOriginalesConfirmacion = [];
        $this->carrerasRetiradasConfirmacion = [];
        $this->impactosAcademicosConfirmacion = [];
        $this->confirmacionRetiroCarreras = false;
    }

    public function guardar(): void
    {
        $this->exigirPermiso($this->editingDocenteId !== null ? 'editar' : 'crear');

        $docenteId = $this->editingDocenteId !== null
            ? (int) $this->editingDocenteId
            : null;
        $docenteOriginal = $docenteId !== null
            ? Docente::query()->findOrFail($docenteId)
            : null;
        $cuentaVinculada = $docenteId !== null
            ? User::query()->where('id_docente', $docenteId)->first()
            : null;
        $jefeCarreraDelMismoDocente = $docenteOriginal !== null
            ? JefeCarrera::query()
                ->where('no_empleado', $docenteOriginal->no_empleado)
                ->where('email', $docenteOriginal->email)
                ->first()
            : null;

        $reglaCorreoDocente = Rule::unique('docentes', 'email');

        if ($docenteId !== null) {
            $reglaCorreoDocente->ignore($docenteId, 'id_docente');
        }

        $reglaCorreoCuenta = Rule::unique('users', 'email');

        if ($cuentaVinculada !== null) {
            $reglaCorreoCuenta->ignore($cuentaVinculada);
        }

        $reglaCorreoJefeCarrera = Rule::unique('jefes_carrera', 'email');

        if ($jefeCarreraDelMismoDocente !== null) {
            $reglaCorreoJefeCarrera->ignore($jefeCarreraDelMismoDocente);
        }

        $reglasCorreo = [
            $reglaCorreoDocente,
            Rule::unique('alumnos', 'email'),
            $reglaCorreoJefeCarrera,
            $reglaCorreoCuenta,
        ];

        $this->validate([
            'nombre' => 'required|min:2|max:50',
            'apellido_paterno' => 'required|min:2|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'no_empleado' => 'required|min:1|max:20'.($this->editingDocenteId ? '|unique:docentes,no_empleado,'.$this->editingDocenteId.',id_docente' : '|unique:docentes,no_empleado'),
            'email' => ['required', 'email', 'max:100', ...$reglasCorreo],
            'carreras_ids' => 'required|array|min:1',
            'carreras_ids.*' => ['integer', Rule::exists('carreras', 'id_carrera')],
        ], [
            'carreras_ids.required' => 'Debes seleccionar al menos una carrera.',
            'carreras_ids.array' => 'La selección de carreras no es válida.',
            'carreras_ids.min' => 'Debes seleccionar al menos una carrera.',
            'carreras_ids.*.integer' => 'Una de las carreras seleccionadas no es válida.',
            'carreras_ids.*.exists' => 'Una de las carreras seleccionadas ya no existe.',
            'email.unique' => 'El correo ya está registrado para otra cuenta o persona.',
        ]);

        $idsCarrerasSeleccionadas = $this->normalizarIdsCarreras((array) $this->carreras_ids);
        $this->carreras_ids = $idsCarrerasSeleccionadas;

        // El perfil, la cuenta que ya exista y sus carreras se guardan de forma atómica.
        try {
            $resultado = DB::transaction(function () use ($docenteId, $idsCarrerasSeleccionadas): string {
                $docente = null;

                if ($docenteId !== null) {
                    $docente = Docente::query()
                        ->whereKey($docenteId)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $idsCarrerasActuales = $this->normalizarIdsCarreras(
                        $docente->carreras()
                            ->orderBy('carreras.id_carrera')
                            ->pluck('carreras.id_carrera')
                            ->all()
                    );
                } else {
                    $idsCarrerasActuales = [];
                }

                $idsCarrerasBloqueadas = $this->normalizarIdsCarreras([
                    ...$idsCarrerasActuales,
                    ...$idsCarrerasSeleccionadas,
                ]);
                $carrerasBloqueadas = Carrera::query()
                    ->whereIn('id_carrera', $idsCarrerasBloqueadas)
                    ->orderBy('id_carrera')
                    ->lockForUpdate()
                    ->get(['id_carrera', 'activo'])
                    ->keyBy('id_carrera');

                if ($carrerasBloqueadas->count() !== count($idsCarrerasBloqueadas)) {
                    $this->addError('carreras_ids', 'Una carrera seleccionada o asignada ya no existe. Revisa las carreras del docente.');

                    return 'invalido';
                }

                $idsCarrerasInactivasNuevas = $carrerasBloqueadas
                    ->filter(fn (Carrera $carrera) => ! $carrera->activo
                        && ! in_array((int) $carrera->id_carrera, $idsCarrerasActuales, true))
                    ->keys()
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($idsCarrerasInactivasNuevas !== []) {
                    $this->addError(
                        'carreras_ids',
                        'No se pueden asignar nuevas carreras inactivas. Las carreras inactivas ya asignadas se conservarán.'
                    );

                    return 'invalido';
                }

                $idsCarrerasRetiradas = array_values(array_diff($idsCarrerasActuales, $idsCarrerasSeleccionadas));
                sort($idsCarrerasRetiradas);
                $impactosAcademicos = $docente !== null && $idsCarrerasRetiradas !== []
                    ? $this->dependenciasAcademicasPorCarreras((int) $docente->id_docente, $idsCarrerasRetiradas)
                    : [];

                if ($this->confirmacionRetiroCarreras
                    && ($this->carrerasOriginalesConfirmacion !== $idsCarrerasActuales
                        || $this->carrerasRetiradasConfirmacion !== $idsCarrerasRetiradas
                        || $this->impactosAcademicosConfirmacion !== $impactosAcademicos)) {
                    $this->limpiarConfirmacionRetiroCarreras();
                    $this->addError(
                        'carreras_ids',
                        'Las carreras o sus asignaciones cambiaron durante la confirmación. Revisa la selección y guarda nuevamente.'
                    );

                    return 'invalido';
                }

                if ($impactosAcademicos !== [] && ! $this->confirmacionRetiroCarreras) {
                    $this->carrerasOriginalesConfirmacion = $idsCarrerasActuales;
                    $this->carrerasRetiradasConfirmacion = $idsCarrerasRetiradas;
                    $this->impactosAcademicosConfirmacion = $impactosAcademicos;

                    return 'confirmacion';
                }

                if ($docente !== null) {
                    $docente->update([
                        'nombre' => $this->nombre,
                        'apellido_paterno' => $this->apellido_paterno,
                        'apellido_materno' => $this->apellido_materno,
                        'no_empleado' => $this->no_empleado,
                        'email' => $this->email,
                    ]);

                    $cuentaVinculada = User::query()
                        ->where('id_docente', $docente->id_docente)
                        ->lockForUpdate()
                        ->first();

                    if ($cuentaVinculada !== null) {
                        $cuentaVinculada->update([
                            'name' => trim($this->nombre.' '.$this->apellido_paterno.' '.trim((string) $this->apellido_materno)),
                            'email' => $this->email,
                        ]);
                    }
                } else {
                    $docente = Docente::create([
                        'nombre' => $this->nombre,
                        'apellido_paterno' => $this->apellido_paterno,
                        'apellido_materno' => $this->apellido_materno,
                        'no_empleado' => $this->no_empleado,
                        'email' => $this->email,
                        'activo' => 1,
                    ]);
                }

                // sync(): agrega las nuevas, elimina las que ya no estén
                // seleccionadas de forma explícita; las relaciones inactivas
                // actuales se mantienen salvo que el administrador las retire.
                $docente->carreras()->sync($idsCarrerasSeleccionadas);

                $this->limpiarConfirmacionRetiroCarreras();

                return 'guardado';
            });
        } catch (UniqueConstraintViolationException) {
            $this->addError('email', 'No se aplicaron cambios: el correo o número de empleado ya fue registrado.');

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('carreras_ids', 'No se pudieron guardar los cambios. No se modificó ningún dato.');

            return;
        }

        if ($resultado === 'confirmacion' || $resultado === 'invalido') {
            return;
        }

        $isEditing = $this->editingDocenteId;

        $this->showModal = false;
        $this->reset([
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'no_empleado',
            'email',
            'carreras_ids',
        ]);
        $this->editingDocenteId = null;
        $this->cargarDocentes();

        session()->flash('success', $isEditing ? 'Docente actualizado correctamente.' : 'Docente registrado exitosamente.');
    }

    /**
     * Lista asignaciones docentes y grupos del docente vinculados a carreras que se retirarían.
     *
     * @param  array<int, int>  $carreraIds
     * @return array<int, array{id_carrera: int, clave: string, nombre: string, asignaciones: int, grupos: int}>
     */
    private function dependenciasAcademicasPorCarreras(int $docenteId, array $carreraIds): array
    {
        if ($carreraIds === []) {
            return [];
        }

        $asignacionesPorCarrera = DB::table('asignaciones_docente as asignacion')
            ->join('materias as materia', 'materia.id_materia', '=', 'asignacion.id_materia')
            ->where('asignacion.id_docente', $docenteId)
            ->whereIn('materia.id_carrera', $carreraIds)
            ->select('materia.id_carrera')
            ->selectRaw('COUNT(DISTINCT asignacion.id_asignacion) AS total')
            ->groupBy('materia.id_carrera')
            ->get()
            ->keyBy('id_carrera');

        $gruposPorCarrera = DB::table('grupos as grupo')
            ->join('materias as materia', 'materia.id_materia', '=', 'grupo.id_materia')
            ->where('grupo.id_docente', $docenteId)
            ->whereIn('materia.id_carrera', $carreraIds)
            ->select('materia.id_carrera')
            ->selectRaw('COUNT(DISTINCT grupo.id_grupo) AS total')
            ->groupBy('materia.id_carrera')
            ->get()
            ->keyBy('id_carrera');

        $carreras = Carrera::query()
            ->whereIn('id_carrera', $carreraIds)
            ->get(['id_carrera', 'clave', 'nombre'])
            ->keyBy('id_carrera');

        $dependencias = [];

        foreach ($carreraIds as $carreraId) {
            $carrera = $carreras->get($carreraId);

            if ($carrera === null) {
                continue;
            }

            $asignaciones = (int) ($asignacionesPorCarrera->get($carreraId)?->total ?? 0);
            $grupos = (int) ($gruposPorCarrera->get($carreraId)?->total ?? 0);

            if ($asignaciones > 0 || $grupos > 0) {
                $dependencias[] = [
                    'id_carrera' => (int) $carrera->id_carrera,
                    'clave' => (string) $carrera->clave,
                    'nombre' => (string) $carrera->nombre,
                    'asignaciones' => $asignaciones,
                    'grupos' => $grupos,
                ];
            }
        }

        return $dependencias;
    }

    public function desactivar(int $docenteId): void
    {
        $this->exigirPermiso('eliminar');
        $this->actualizarEstado($docenteId, false);
    }

    public function reactivar(int $docenteId): void
    {
        $this->exigirPermiso('editar');
        $this->actualizarEstado($docenteId, true);
    }

    private function actualizarEstado(int $docenteId, bool $activo): void
    {
        try {
            $estadoActualizado = DB::transaction(function () use ($docenteId, $activo): bool {
                $docente = Docente::query()
                    ->whereKey($docenteId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((bool) $docente->activo === $activo) {
                    return false;
                }

                $docente->activo = $activo;
                $docente->save();

                return true;
            });
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash(
                'error',
                $activo
                    ? 'No se pudo reactivar al docente. No se modificó ningún dato.'
                    : 'No se pudo desactivar al docente. No se modificó ningún dato.'
            );

            return;
        }

        $this->cargarDocentes();

        session()->flash(
            'success',
            $estadoActualizado
                ? ($activo ? 'Docente reactivado correctamente.' : 'Docente desactivado correctamente. Se conservaron su cuenta y sus relaciones académicas.')
                : ($activo ? 'El docente ya estaba activo.' : 'El docente ya estaba inactivo.')
        );
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        return view('admin.docentes.index', [
            'puedeCrear' => Gate::allows('Docentes.crear'),
            'puedeEditar' => Gate::allows('Docentes.editar'),
            'puedeEliminar' => Gate::allows('Docentes.eliminar'),
        ]);
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::allows('Docentes.'.$accion), 403);
    }
}
