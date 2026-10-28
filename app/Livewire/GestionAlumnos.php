<?php

namespace App\Livewire;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\PeriodoEscolar;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class GestionAlumnos extends Component
{
    use WithPagination;

    public $search = '';

    public $filtroCarrera = '';

    public $id_carrera = '';

    public $no_control = '';

    public $nombre = '';

    public $apellido_paterno = '';

    public $apellido_materno = '';

    public $email = '';

    public $editingAlumnoId = null;

    public $showModal = false;

    /**
     * @var array{
     *     alumno_id: int,
     *     usuario_id: int|null,
     *     carrera_anterior_id: int,
     *     carrera_nueva_id: int,
     *     carrera_anterior: string,
     *     carrera_nueva: string,
     *     datos: array<string, int|string|null>
     * }|null
     */
    #[Locked]
    public ?array $confirmacionCambioCarreraPendiente = null;

    public $alumnoCount = 0;

    public $alumnosActivosCount = 0;

    public $alumnosInactivosCount = 0;

    public $carrerasConAlumnosCount = 0;

    // Catálogos: carreras activas para altas y filtro completo para consulta.
    public $carreras = [];

    public $carrerasFiltro = [];

    public function mount()
    {
        $this->exigirPermiso('ver');
        $this->cargarAlumnos();
        $this->cargarSelects();
    }

    public function cargarSelects(?int $carreraPreseleccionada = null): void
    {
        $this->exigirPermiso('ver');

        $this->carreras = Carrera::query()
            ->where(function (Builder $query) use ($carreraPreseleccionada): void {
                $query->where('activo', 1);

                if ($carreraPreseleccionada !== null) {
                    $query->orWhere('id_carrera', $carreraPreseleccionada);
                }
            })
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();

        $this->carrerasFiltro = Carrera::query()
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();
    }

    public function cargarAlumnos(): void
    {
        $this->exigirPermiso('ver');

        $this->alumnoCount = Alumno::count();
        $this->alumnosActivosCount = Alumno::where('activo', 1)->count();
        $this->alumnosInactivosCount = Alumno::where('activo', 0)->count();
        $this->carrerasConAlumnosCount = Alumno::query()
            ->distinct()
            ->count('id_carrera');
    }

    public function updatedSearch(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage();
    }

    public function updatedFiltroCarrera(): void
    {
        $this->exigirPermiso('ver');
        $this->resetPage();
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');
        $this->confirmacionCambioCarreraPendiente = null;
        $this->editingAlumnoId = null;
        $this->reset(['id_carrera', 'no_control', 'nombre', 'apellido_paterno', 'apellido_materno', 'email']);
        $this->cargarSelects();
        $this->showModal = true;
    }

    public function abrirModalEditar($alumnoId): void
    {
        $this->exigirPermiso('editar');
        $alumno = Alumno::findOrFail($alumnoId);

        $this->confirmacionCambioCarreraPendiente = null;
        $this->editingAlumnoId = $alumnoId;
        $this->id_carrera = $alumno->id_carrera;
        $this->no_control = $alumno->no_control;
        $this->nombre = $alumno->nombre;
        $this->apellido_paterno = $alumno->apellido_paterno;
        $this->apellido_materno = $alumno->apellido_materno;
        $this->email = $alumno->email;
        $this->cargarSelects((int) $alumno->id_carrera);
        $this->showModal = true;
    }

    public function cerrarModal(): void
    {
        $this->showModal = false;
        $this->confirmacionCambioCarreraPendiente = null;
    }

    public function guardar(): void
    {
        $esEdicion = $this->editingAlumnoId !== null;
        $this->exigirPermiso($esEdicion ? 'editar' : 'crear');

        if ($this->confirmacionCambioCarreraPendiente !== null) {
            $this->addError('id_carrera', 'Confirma o cancela el cambio de carrera antes de volver a guardar.');

            return;
        }

        $alumno = $esEdicion ? Alumno::findOrFail($this->editingAlumnoId) : null;
        $cuentaVinculada = $alumno === null
            ? null
            : User::query()->where('id_alumno', $alumno->id_alumno)->first();
        $data = $this->validarDatosFormulario($alumno, $cuentaVinculada);

        if ($data === null) {
            return;
        }

        if ($alumno !== null && $data['id_carrera'] !== (int) $alumno->id_carrera) {
            $this->solicitarConfirmacionCambioCarrera($alumno, $cuentaVinculada, $data);

            return;
        }

        if (! $this->guardarEnTransaccion($alumno, $cuentaVinculada, $data)) {
            return;
        }

        $this->finalizarGuardado($alumno !== null);
    }

    public function confirmarCambioCarrera(): void
    {
        $this->exigirPermiso('editar');
        $pendiente = $this->confirmacionCambioCarreraPendiente;

        if ($pendiente === null) {
            $this->addError('id_carrera', 'No hay un cambio de carrera pendiente de confirmación.');

            return;
        }

        if ((int) $this->editingAlumnoId !== $pendiente['alumno_id']) {
            $this->confirmacionCambioCarreraPendiente = null;
            $this->addError('id_carrera', 'El alumno seleccionado cambió. Vuelve a abrir el formulario.');

            return;
        }

        $alumno = Alumno::findOrFail($pendiente['alumno_id']);
        $cuentaVinculada = User::query()->where('id_alumno', $alumno->id_alumno)->first();

        try {
            $data = $this->validarDatosFormulario($alumno, $cuentaVinculada);
        } catch (ValidationException $exception) {
            $this->confirmacionCambioCarreraPendiente = null;

            throw $exception;
        }

        if ($data === null) {
            $this->confirmacionCambioCarreraPendiente = null;

            return;
        }

        if ($data !== $pendiente['datos']) {
            $this->confirmacionCambioCarreraPendiente = null;
            $this->addError('id_carrera', 'Los datos del formulario cambiaron después de la confirmación. Revisa y vuelve a guardar.');

            return;
        }

        if (! $this->guardarEnTransaccion($alumno, $cuentaVinculada, $data, $pendiente)) {
            $this->confirmacionCambioCarreraPendiente = null;

            return;
        }

        $this->finalizarGuardado(true);
    }

    public function cancelarConfirmacionCambioCarrera(): void
    {
        $this->exigirPermiso('editar');

        if ($this->confirmacionCambioCarreraPendiente === null) {
            return;
        }

        $this->id_carrera = (string) $this->confirmacionCambioCarreraPendiente['carrera_anterior_id'];
        $this->confirmacionCambioCarreraPendiente = null;
        $this->resetValidation('id_carrera');
    }

    /** @param array<string, int|string|null> $data */
    private function validarDatosFormulario(?Alumno $alumno, ?User $cuentaVinculada): ?array
    {
        $reglaNoControl = Rule::unique('alumnos', 'no_control');
        $reglaCorreo = Rule::unique('alumnos', 'email');
        $reglaCorreoCuenta = Rule::unique('users', 'email');

        if ($alumno !== null) {
            $reglaNoControl->ignore($alumno->id_alumno, 'id_alumno');
            $reglaCorreo->ignore($alumno->id_alumno, 'id_alumno');
        }

        if ($cuentaVinculada !== null) {
            $reglaCorreoCuenta->ignore($cuentaVinculada->id);
        }

        $reglaCarrera = Rule::exists('carreras', 'id_carrera');

        if ($alumno === null) {
            $reglaCarrera->where('activo', 1);
        }

        $this->validate([
            'id_carrera' => ['required', 'integer', $reglaCarrera],
            'no_control' => ['required', 'string', 'min:3', 'max:20', $reglaNoControl],
            'nombre' => ['required', 'string', 'min:2', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'min:2', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:100',
                $reglaCorreo,
                $reglaCorreoCuenta,
                Rule::unique('docentes', 'email'),
                Rule::unique('jefes_carrera', 'email'),
            ],
        ], [
            'id_carrera.required' => 'Debes seleccionar una carrera activa.',
            'id_carrera.exists' => 'La carrera seleccionada no existe o no está activa.',
            'no_control.required' => 'El número de control es obligatorio.',
            'no_control.min' => 'El número de control debe tener al menos 3 caracteres.',
            'no_control.max' => 'El número de control no puede superar los 20 caracteres.',
            'no_control.unique' => 'Ese número de control ya está registrado.',
            'nombre.required' => 'El nombre del alumno es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_paterno.min' => 'El apellido paterno debe tener al menos 2 caracteres.',
            'apellido_paterno.max' => 'El apellido paterno no puede superar los 50 caracteres.',
            'apellido_materno.max' => 'El apellido materno no puede superar los 50 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 100 caracteres.',
            'email.unique' => 'Ese correo ya está registrado para otra cuenta o persona.',
        ]);

        $idCarreraSeleccionada = (int) $this->id_carrera;

        if ($alumno !== null
            && $idCarreraSeleccionada !== (int) $alumno->id_carrera
            && ! Carrera::query()->whereKey($idCarreraSeleccionada)->where('activo', 1)->exists()) {
            $this->addError('id_carrera', 'No se puede asignar una carrera inactiva a un alumno.');

            return null;
        }

        return [
            'id_carrera' => $idCarreraSeleccionada,
            'no_control' => (string) $this->no_control,
            'nombre' => (string) $this->nombre,
            'apellido_paterno' => (string) $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno === null ? null : (string) $this->apellido_materno,
            'email' => trim((string) $this->email),
        ];
    }

    /** @param array<string, int|string|null> $data */
    private function solicitarConfirmacionCambioCarrera(Alumno $alumno, ?User $cuentaVinculada, array $data): void
    {
        try {
            $pendiente = DB::transaction(function () use ($alumno, $cuentaVinculada, $data): ?array {
                $this->exigirPermiso('editar');

                $alumnoBloqueado = Alumno::query()
                    ->whereKey($alumno->id_alumno)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $alumnoBloqueado->id_carrera !== (int) $alumno->id_carrera) {
                    $this->addError('id_carrera', 'La carrera actual cambió. Vuelve a abrir el formulario antes de solicitar el cambio.');

                    return null;
                }

                $cuentaBloqueada = User::query()
                    ->where('id_alumno', $alumnoBloqueado->id_alumno)
                    ->lockForUpdate()
                    ->first();

                if (($cuentaBloqueada?->id) !== ($cuentaVinculada?->id)) {
                    $this->addError('email', 'La cuenta vinculada cambió durante la edición. Vuelve a abrir el formulario.');

                    return null;
                }

                $carreraAnterior = Carrera::query()
                    ->whereKey($alumnoBloqueado->id_carrera)
                    ->lockForUpdate()
                    ->first();
                $carreraNueva = Carrera::query()
                    ->whereKey($data['id_carrera'])
                    ->lockForUpdate()
                    ->first();

                if ($carreraAnterior === null || $carreraNueva === null) {
                    $this->addError('id_carrera', 'La carrera actual o la seleccionada ya no existe. Revisa el formulario.');

                    return null;
                }

                if (! $carreraNueva->activo) {
                    $this->addError('id_carrera', 'La carrera destino debe continuar activa para solicitar el cambio.');

                    return null;
                }

                $problemaInscripciones = $this->problemaInscripcionesCambioCarrera(
                    (int) $alumnoBloqueado->id_alumno,
                    true
                );

                if ($problemaInscripciones !== null) {
                    $this->addError('id_carrera', $problemaInscripciones);

                    return null;
                }

                return [
                    'alumno_id' => (int) $alumnoBloqueado->id_alumno,
                    'usuario_id' => $cuentaBloqueada?->id === null ? null : (int) $cuentaBloqueada->id,
                    'carrera_anterior_id' => (int) $carreraAnterior->id_carrera,
                    'carrera_nueva_id' => (int) $carreraNueva->id_carrera,
                    'carrera_anterior' => (string) $carreraAnterior->nombre,
                    'carrera_nueva' => (string) $carreraNueva->nombre,
                    'datos' => $data,
                ];
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('id_carrera', 'No se pudo verificar el cambio de carrera. No se guardaron cambios. Intenta nuevamente.');

            return;
        }

        if ($pendiente !== null) {
            $this->confirmacionCambioCarreraPendiente = $pendiente;
        }
    }

    /**
     * @param  array<string, int|string|null>  $data
     * @param array{
     *     alumno_id: int,
     *     usuario_id: int|null,
     *     carrera_anterior_id: int,
     *     carrera_nueva_id: int,
     *     carrera_anterior: string,
     *     carrera_nueva: string,
     *     datos: array<string, int|string|null>
     * }|null $confirmacion
     */
    private function guardarEnTransaccion(
        ?Alumno $alumno,
        ?User $cuentaVinculada,
        array $data,
        ?array $confirmacion = null
    ): bool {
        try {
            return DB::transaction(function () use ($alumno, $cuentaVinculada, $data, $confirmacion): bool {
                $this->exigirPermiso($alumno === null ? 'crear' : 'editar');

                $alumnoBloqueado = $alumno === null
                    ? null
                    : Alumno::query()->whereKey($alumno->id_alumno)->lockForUpdate()->firstOrFail();
                $cuentaBloqueada = $alumnoBloqueado === null
                    ? null
                    : User::query()
                        ->where('id_alumno', $alumnoBloqueado->id_alumno)
                        ->lockForUpdate()
                        ->first();

                if (($cuentaBloqueada?->id) !== ($cuentaVinculada?->id)) {
                    $this->addError('email', 'La cuenta vinculada cambió durante la edición. Vuelve a abrir el formulario.');

                    return false;
                }

                if ($confirmacion !== null) {
                    if ($alumnoBloqueado === null
                        || (int) $alumnoBloqueado->id_alumno !== $confirmacion['alumno_id']
                        || (int) $alumnoBloqueado->id_carrera !== $confirmacion['carrera_anterior_id']
                        || (int) $data['id_carrera'] !== $confirmacion['carrera_nueva_id']) {
                        $this->addError('id_carrera', 'El perfil o la carrera original cambió desde que se solicitó la confirmación. Revisa y vuelve a guardar.');

                        return false;
                    }

                    if (($cuentaBloqueada?->id === null ? null : (int) $cuentaBloqueada->id) !== $confirmacion['usuario_id']) {
                        $this->addError('email', 'La cuenta vinculada cambió desde que se solicitó la confirmación. Vuelve a abrir el formulario.');

                        return false;
                    }

                    $carreraAnterior = Carrera::query()
                        ->whereKey($confirmacion['carrera_anterior_id'])
                        ->lockForUpdate()
                        ->first();
                    $carreraNueva = Carrera::query()
                        ->whereKey($confirmacion['carrera_nueva_id'])
                        ->lockForUpdate()
                        ->first();

                    if ($carreraAnterior === null || $carreraNueva === null) {
                        $this->addError('id_carrera', 'La carrera actual o la seleccionada ya no existe. No se guardaron cambios.');

                        return false;
                    }

                    if (! $carreraNueva->activo) {
                        $this->addError('id_carrera', 'La carrera destino dejó de estar activa. No se guardaron cambios.');

                        return false;
                    }

                    if ($carreraAnterior->nombre !== $confirmacion['carrera_anterior']
                        || $carreraNueva->nombre !== $confirmacion['carrera_nueva']) {
                        $this->addError('id_carrera', 'Los datos de las carreras cambiaron durante la confirmación. Revisa y solicita el cambio nuevamente.');

                        return false;
                    }

                    $problemaInscripciones = $this->problemaInscripcionesCambioCarrera(
                        (int) $alumnoBloqueado->id_alumno,
                        true
                    );

                    if ($problemaInscripciones !== null) {
                        $this->addError('id_carrera', $problemaInscripciones);

                        return false;
                    }
                } elseif ($alumnoBloqueado !== null
                    && (int) $alumnoBloqueado->id_carrera !== (int) $data['id_carrera']) {
                    $this->addError('id_carrera', 'La carrera actual cambió durante la edición. Solicita nuevamente el cambio antes de guardar.');

                    return false;
                }

                if ($this->correoOcupadoPorOtraPersona(
                    (string) $data['email'],
                    $alumnoBloqueado?->id_alumno,
                    $cuentaBloqueada?->id
                )) {
                    $this->addError('email', 'Ese correo ya está registrado para otra cuenta o persona. No se guardaron cambios.');

                    return false;
                }

                if ($alumnoBloqueado !== null) {
                    if (! $alumnoBloqueado->update($data)) {
                        throw new \RuntimeException('No se pudo actualizar el perfil del alumno.');
                    }

                    if ($cuentaBloqueada !== null) {
                        $nombreCompleto = trim(
                            $data['nombre'].' '.$data['apellido_paterno'].' '.trim((string) $data['apellido_materno'])
                        );

                        if (! $cuentaBloqueada->update([
                            'name' => $nombreCompleto,
                            'email' => $data['email'],
                        ])) {
                            throw new \RuntimeException('No se pudo actualizar la cuenta vinculada del alumno.');
                        }
                    }
                } else {
                    Alumno::create($data + ['activo' => 1]);
                }

                return true;
            });
        } catch (UniqueConstraintViolationException $exception) {
            $this->addError('email', 'El correo o número de control ya está registrado. No se guardaron cambios.');

            return false;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('email', 'No se pudieron guardar los cambios del alumno y su cuenta. La operación se revirtió sin cambios parciales.');

            return false;
        }
    }

    private function problemaInscripcionesCambioCarrera(int $alumnoId, bool $bloquearFilas): ?string
    {
        $consultas = [
            DB::table('inscripciones as i')
                ->leftJoin('grupos as g', 'g.id_grupo', '=', 'i.id_grupo')
                ->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia')
                ->leftJoin('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
                ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo')
                ->where('i.id_alumno', $alumnoId)
                ->select([
                    'g.id_grupo as grupo_encontrado',
                    'g.id_materia',
                    'm.id_materia as materia_encontrada',
                    'm.id_carrera',
                    'c.id_carrera as carrera_encontrada',
                    'g.id_periodo',
                    'p.id_periodo as periodo_encontrado',
                    'p.estado as periodo_estado',
                    'p.activo as periodo_activo',
                ]),
            DB::table('inscripcion_grupos as ig')
                ->join('inscripciones as i', 'i.id_inscripcion', '=', 'ig.id_inscripcion')
                ->leftJoin('grupos as g', 'g.id_grupo', '=', 'ig.id_grupo')
                ->leftJoin('materias as m', 'm.id_materia', '=', 'g.id_materia')
                ->leftJoin('carreras as c', 'c.id_carrera', '=', 'm.id_carrera')
                ->leftJoin('periodo_escolars as p', 'p.id_periodo', '=', 'g.id_periodo')
                ->where('i.id_alumno', $alumnoId)
                ->select([
                    'g.id_grupo as grupo_encontrado',
                    'g.id_materia',
                    'm.id_materia as materia_encontrada',
                    'm.id_carrera',
                    'c.id_carrera as carrera_encontrada',
                    'g.id_periodo',
                    'p.id_periodo as periodo_encontrado',
                    'p.estado as periodo_estado',
                    'p.activo as periodo_activo',
                ]),
        ];

        foreach ($consultas as $consulta) {
            if ($bloquearFilas) {
                $consulta->lockForUpdate();
            }

            foreach ($consulta->get() as $inscripcion) {
                if ($inscripcion->grupo_encontrado === null
                    || $inscripcion->id_materia === null
                    || $inscripcion->materia_encontrada === null
                    || $inscripcion->id_carrera === null
                    || $inscripcion->carrera_encontrada === null
                    || $inscripcion->id_periodo === null
                    || $inscripcion->periodo_encontrado === null) {
                    return 'No se puede cambiar la carrera porque una inscripción tiene relaciones académicas incompletas. Consulta a Control Escolar.';
                }

                if (! in_array($inscripcion->periodo_estado, PeriodoEscolar::ESTADOS, true)) {
                    return 'No se puede cambiar la carrera porque una inscripción está asociada a un periodo con estado desconocido.';
                }

                if ($inscripcion->periodo_estado === 'Activo') {
                    return 'No se puede cambiar la carrera: el alumno tiene inscripciones asociadas a un periodo escolar activo.';
                }

                if ($inscripcion->periodo_estado === 'Próximo') {
                    return 'No se puede cambiar la carrera: el alumno tiene inscripciones asociadas a un periodo escolar próximo.';
                }

                if ((bool) $inscripcion->periodo_activo) {
                    return 'No se puede cambiar la carrera porque el periodo finalizado tiene un estado activo inconsistente.';
                }
            }
        }

        return null;
    }

    private function finalizarGuardado(bool $esEdicion): void
    {
        $this->showModal = false;
        $this->confirmacionCambioCarreraPendiente = null;
        $this->reset(['id_carrera', 'no_control', 'nombre', 'apellido_paterno', 'apellido_materno', 'email']);
        $this->editingAlumnoId = null;
        $this->cargarSelects();
        $this->cargarAlumnos();

        session()->flash('success', $esEdicion
            ? 'Alumno actualizado correctamente.'
            : 'Alumno registrado exitosamente.');
    }

    private function correoOcupadoPorOtraPersona(string $email, ?int $alumnoId, ?int $usuarioId): bool
    {
        $alumnoQuery = DB::table('alumnos')->where('email', $email);

        if ($alumnoId !== null) {
            $alumnoQuery->where('id_alumno', '<>', $alumnoId);
        }

        if ($alumnoQuery->exists()) {
            return true;
        }

        $usuarioQuery = DB::table('users')->where('email', $email);

        if ($usuarioId !== null) {
            $usuarioQuery->where('id', '<>', $usuarioId);
        }

        return $usuarioQuery->exists()
            || DB::table('docentes')->where('email', $email)->exists()
            || DB::table('jefes_carrera')->where('email', $email)->exists();
    }

    public function desactivar(int $alumnoId): void
    {
        $this->exigirPermiso('eliminar');
        $this->actualizarEstadoAlumno($alumnoId, false);
    }

    public function reactivar(int $alumnoId): void
    {
        $this->exigirPermiso('editar');
        $this->actualizarEstadoAlumno($alumnoId, true);
    }

    /**
     * Compatibilidad segura con clientes Livewire antiguos: esta acción
     * únicamente desactiva el perfil y nunca elimina registros.
     */
    public function eliminar(int $alumnoId): void
    {
        $this->desactivar($alumnoId);
    }

    private function actualizarEstadoAlumno(int $alumnoId, bool $activo): void
    {
        try {
            $estadoActualizado = DB::transaction(function () use ($alumnoId, $activo): bool {
                $alumno = Alumno::query()
                    ->whereKey($alumnoId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((bool) $alumno->activo === $activo) {
                    return false;
                }

                $alumno->activo = $activo;
                $alumno->save();

                return true;
            });
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash(
                'error',
                $activo
                    ? 'No se pudo reactivar al alumno. No se modificó ningún dato.'
                    : 'No se pudo desactivar al alumno. No se modificó ningún dato.'
            );

            return;
        }

        $this->cargarAlumnos();

        session()->flash(
            'success',
            $estadoActualizado
                ? ($activo
                    ? 'Alumno reactivado correctamente.'
                    : 'Alumno desactivado correctamente. Se conservaron su cuenta y sus relaciones académicas.')
                : ($activo ? 'El alumno ya estaba activo.' : 'El alumno ya estaba inactivo.')
        );
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        $alumnos = $this->consultaAlumnos()->paginate(15);
        $usuarioActual = Auth::user();

        return view('admin.alumnos.index', [
            'alumnos' => $alumnos,
            'alumnosFiltradosCount' => $alumnos->total(),
            'puedeCrear' => $usuarioActual?->tienePermiso('Alumnos', 'crear') ?? false,
            'puedeEditar' => $usuarioActual?->tienePermiso('Alumnos', 'editar') ?? false,
            'puedeEliminar' => $usuarioActual?->tienePermiso('Alumnos', 'eliminar') ?? false,
        ]);
    }

    private function consultaAlumnos(): Builder
    {
        $busqueda = trim((string) $this->search);

        return Alumno::query()
            ->with('carrera')
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $subquery) use ($busqueda): void {
                    $subquery->where('no_control', 'like', "%{$busqueda}%")
                        ->orWhere('nombre', 'like', "%{$busqueda}%")
                        ->orWhere('apellido_paterno', 'like', "%{$busqueda}%")
                        ->orWhere('apellido_materno', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%")
                        ->orWhereHas('carrera', function (Builder $carreraQuery) use ($busqueda): void {
                            $carreraQuery->where('nombre', 'like', "%{$busqueda}%")
                                ->orWhere('clave', 'like', "%{$busqueda}%");
                        });
                });
            })
            ->when($this->filtroCarrera !== '', function (Builder $query): void {
                $query->where('id_carrera', (int) $this->filtroCarrera);
            })
            ->orderBy('apellido_paterno')
            ->orderBy('nombre')
            ->orderBy('id_alumno');
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::allows('Alumnos.'.$accion), 403);
    }
}
