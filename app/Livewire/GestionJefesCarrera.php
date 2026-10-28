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

class GestionJefesCarrera extends Component
{
    public $jefes = [];

    public $carreras = [];

    public $search = '';

    // Datos personales del Jefe de Carrera (se registran directamente)
    public $nombre = '';

    public $apellido_paterno = '';

    public $apellido_materno = '';

    public $no_empleado = '';

    public $email = '';

    public $id_carrera = '';

    public $fecha_inicio = '';

    #[Locked]
    public $editingJefeId = null;

    #[Locked]
    public ?int $carreraAnteriorConfirmacionId = null;

    #[Locked]
    public ?int $carreraNuevaConfirmacionId = null;

    #[Locked]
    public string $nombreCarreraAnteriorConfirmacion = '';

    #[Locked]
    public string $nombreCarreraNuevaConfirmacion = '';

    #[Locked]
    public bool $confirmacionCambioCarrera = false;

    public $showModal = false;

    public $jefeCount = 0;

    public function mount(): void
    {
        $this->exigirPermiso('ver');
        $this->cargarJefes();
        $this->cargarSelects();
    }

    public function cargarSelects(?JefeCarrera $jefeActual = null): void
    {
        $this->exigirPermiso('ver');

        $idCarreraActual = $jefeActual?->id_carrera;

        $this->carreras = Carrera::query()
            ->where(function ($query) use ($idCarreraActual): void {
                $query->where('activo', 1);

                if ($idCarreraActual !== null) {
                    $query->orWhere('id_carrera', $idCarreraActual);
                }
            })
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();
    }

    public function cargarJefes(): void
    {
        $this->exigirPermiso('ver');

        $query = JefeCarrera::with('carrera');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('apellido_paterno', 'like', "%{$search}%")
                    ->orWhere('apellido_materno', 'like', "%{$search}%")
                    ->orWhere('no_empleado', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('carrera', function ($qc) use ($search) {
                        $qc->where('nombre', 'like', "%{$search}%")
                            ->orWhere('clave', 'like', "%{$search}%");
                    });
            });
        }

        $this->jefes = $query->get()->toArray();
        $this->jefeCount = count($this->jefes);
    }

    public function updatedSearch(): void
    {
        $this->exigirPermiso('ver');
        $this->cargarJefes();
    }

    public function updatedIdCarrera(): void
    {
        $this->exigirPermiso($this->editingJefeId !== null ? 'editar' : 'crear');
        $this->limpiarConfirmacionCambioCarrera();
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');
        $this->editingJefeId = null;
        $this->limpiarConfirmacionCambioCarrera();
        $this->reset(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email', 'id_carrera', 'fecha_inicio']);
        $this->cargarSelects();
        $this->showModal = true;
    }

    public function abrirModalEditar(int $jefeId): void
    {
        $this->exigirPermiso('editar');
        $jefe = JefeCarrera::findOrFail($jefeId);

        $this->editingJefeId = $jefeId;
        $this->nombre = $jefe->nombre;
        $this->apellido_paterno = $jefe->apellido_paterno;
        $this->apellido_materno = $jefe->apellido_materno;
        $this->no_empleado = $jefe->no_empleado;
        $this->email = $jefe->email;
        $this->id_carrera = $jefe->id_carrera;
        $this->fecha_inicio = $jefe->fecha_inicio;
        $this->limpiarConfirmacionCambioCarrera();
        $this->cargarSelects($jefe);
        $this->showModal = true;
    }

    public function confirmarCambioCarrera(): void
    {
        $this->exigirPermiso('editar');

        if ($this->carreraAnteriorConfirmacionId === null
            || $this->carreraNuevaConfirmacionId === null) {
            $this->addError('id_carrera', 'No hay un cambio de carrera pendiente de confirmación.');

            return;
        }

        $this->confirmacionCambioCarrera = true;
        $this->guardar();
    }

    public function cancelarCambioCarrera(): void
    {
        $this->exigirPermiso('editar');

        if ($this->carreraAnteriorConfirmacionId !== null) {
            $this->id_carrera = (string) $this->carreraAnteriorConfirmacionId;
        }

        $this->limpiarConfirmacionCambioCarrera();
    }

    private function limpiarConfirmacionCambioCarrera(): void
    {
        $this->carreraAnteriorConfirmacionId = null;
        $this->carreraNuevaConfirmacionId = null;
        $this->nombreCarreraAnteriorConfirmacion = '';
        $this->nombreCarreraNuevaConfirmacion = '';
        $this->confirmacionCambioCarrera = false;
    }

    public function guardar(): void
    {
        $jefeId = $this->editingJefeId !== null ? (int) $this->editingJefeId : null;
        $this->exigirPermiso($jefeId !== null ? 'editar' : 'crear');

        $jefeOriginal = $jefeId !== null
            ? JefeCarrera::query()->findOrFail($jefeId)
            : null;
        $cuentaVinculada = $jefeId !== null
            ? User::query()->where('id_jefe_carrera', $jefeId)->first()
            : null;

        $docenteHistoricoMismoIdentificador = null;

        if ($jefeOriginal !== null
            && trim((string) $this->no_empleado) === trim((string) $jefeOriginal->no_empleado)) {
            $docenteHistorico = Docente::query()
                ->where('no_empleado', $jefeOriginal->no_empleado)
                ->first();

            if ($docenteHistorico !== null
                && mb_strtolower(trim((string) $this->email)) === mb_strtolower(trim((string) $docenteHistorico->email))) {
                $docenteHistoricoMismoIdentificador = $docenteHistorico;
            }
        }

        $reglaNumeroEmpleado = Rule::unique('jefes_carrera', 'no_empleado');
        $reglaCorreoJefe = Rule::unique('jefes_carrera', 'email');
        $reglaCorreoUsuario = Rule::unique('users', 'email');
        $reglaCorreoDocente = Rule::unique('docentes', 'email');

        if ($jefeOriginal !== null) {
            $reglaNumeroEmpleado->ignore($jefeOriginal);
            $reglaCorreoJefe->ignore($jefeOriginal);
        }

        if ($cuentaVinculada !== null) {
            $reglaCorreoUsuario->ignore($cuentaVinculada);
        }

        if ($docenteHistoricoMismoIdentificador !== null) {
            $reglaCorreoDocente->ignore($docenteHistoricoMismoIdentificador);
        }

        $this->validate([
            'nombre' => ['required', 'string', 'min:2', 'max:50'],
            'apellido_paterno' => ['required', 'string', 'min:2', 'max:50'],
            'apellido_materno' => ['nullable', 'string', 'max:50'],
            'no_empleado' => ['required', 'string', 'min:1', 'max:20', $reglaNumeroEmpleado],
            'email' => [
                'required',
                'email',
                'max:100',
                $reglaCorreoJefe,
                $reglaCorreoUsuario,
                $reglaCorreoDocente,
                Rule::unique('alumnos', 'email'),
            ],
            'id_carrera' => ['required', 'integer', Rule::exists('carreras', 'id_carrera')],
            'fecha_inicio' => ['required', 'date'],
        ], [
            'no_empleado.unique' => 'Ese número de empleado ya pertenece a otro Jefe de Carrera.',
            'email.unique' => 'El correo ya pertenece a otra cuenta o persona.',
            'id_carrera.exists' => 'La carrera seleccionada no existe.',
        ]);

        $datos = [
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'no_empleado' => trim((string) $this->no_empleado),
            'email' => trim((string) $this->email),
            'id_carrera' => (int) $this->id_carrera,
            'fecha_inicio' => $this->fecha_inicio,
        ];

        $nombreCuenta = trim($this->nombre.' '.$this->apellido_paterno.' '.trim((string) $this->apellido_materno));

        try {
            $resultado = DB::transaction(function () use ($datos, $jefeId, $nombreCuenta): string {
                if ($jefeId !== null) {
                    $jefe = JefeCarrera::query()
                        ->whereKey($jefeId)
                        ->lockForUpdate()
                        ->firstOrFail();
                    $idCarreraActual = (int) $jefe->id_carrera;
                    $carreraOriginal = Carrera::query()
                        ->whereKey($idCarreraActual)
                        ->lockForUpdate()
                        ->firstOrFail();
                } else {
                    $jefe = null;
                    $idCarreraActual = null;
                    $carreraOriginal = null;
                }

                $carreraNueva = Carrera::query()
                    ->whereKey($datos['id_carrera'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $carreraNueva->activo && $datos['id_carrera'] !== $idCarreraActual) {
                    $this->limpiarConfirmacionCambioCarrera();
                    $this->addError('id_carrera', 'La nueva carrera debe estar activa. La carrera inactiva actual puede conservarse.');

                    return 'invalido';
                }

                $cambioCarrera = $jefe !== null && $datos['id_carrera'] !== $idCarreraActual;

                if ($this->confirmacionCambioCarrera
                    && ($jefe === null
                        || $this->carreraAnteriorConfirmacionId !== $idCarreraActual
                        || $this->carreraNuevaConfirmacionId !== $datos['id_carrera'])) {
                    $this->limpiarConfirmacionCambioCarrera();
                    $this->addError('id_carrera', 'La carrera cambió mientras se confirmaba. Revisa la selección y vuelve a guardar.');

                    return 'invalido';
                }

                if ($cambioCarrera && ! $this->confirmacionCambioCarrera) {
                    $this->carreraAnteriorConfirmacionId = $idCarreraActual;
                    $this->carreraNuevaConfirmacionId = $datos['id_carrera'];
                    $this->nombreCarreraAnteriorConfirmacion = $carreraOriginal->clave.' · '.$carreraOriginal->nombre;
                    $this->nombreCarreraNuevaConfirmacion = $carreraNueva->clave.' · '.$carreraNueva->nombre;

                    return 'confirmacion';
                }

                if ($jefe !== null) {
                    $jefe->update($datos);

                    $cuentaVinculada = User::query()
                        ->where('id_jefe_carrera', $jefe->id_jefe_carrera)
                        ->lockForUpdate()
                        ->first();

                    if ($cuentaVinculada !== null) {
                        $cuentaVinculada->update([
                            'name' => $nombreCuenta,
                            'email' => $datos['email'],
                        ]);
                    }
                } else {
                    JefeCarrera::create($datos + ['activo' => 1]);
                }

                return 'guardado';
            });
        } catch (UniqueConstraintViolationException) {
            $this->addError('email', 'El correo o número de empleado ya pertenece a otra cuenta o persona. No se guardaron cambios.');

            return;
        } catch (\Throwable $exception) {
            report($exception);
            $this->addError('email', 'No se pudieron guardar los cambios. No se modificó ningún dato.');

            return;
        }

        if ($resultado === 'confirmacion' || $resultado === 'invalido') {
            return;
        }

        $isEditing = $this->editingJefeId;

        $this->showModal = false;
        $this->reset(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email', 'id_carrera', 'fecha_inicio']);
        $this->editingJefeId = null;
        $this->limpiarConfirmacionCambioCarrera();
        $this->cargarJefes();

        session()->flash('success', $isEditing
            ? 'Jefe de carrera actualizado correctamente.'
            : 'Jefe de carrera registrado exitosamente.');
    }

    public function desactivar(int $jefeId): void
    {
        $this->exigirPermiso('eliminar');
        $this->actualizarEstado($jefeId, false);
    }

    public function reactivar(int $jefeId): void
    {
        $this->exigirPermiso('editar');
        $this->actualizarEstado($jefeId, true);
    }

    private function actualizarEstado(int $jefeId, bool $activo): void
    {
        try {
            $estadoActualizado = DB::transaction(function () use ($jefeId, $activo): bool {
                $jefe = JefeCarrera::query()
                    ->whereKey($jefeId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((bool) $jefe->activo === $activo) {
                    return false;
                }

                $jefe->activo = $activo;
                $jefe->save();

                return true;
            });
        } catch (\Throwable $exception) {
            report($exception);
            session()->flash(
                'error',
                $activo
                    ? 'No se pudo reactivar el perfil. No se modificó ningún dato.'
                    : 'No se pudo desactivar el perfil. No se modificó ningún dato.'
            );

            return;
        }

        $this->cargarJefes();

        session()->flash(
            'success',
            $estadoActualizado
                ? ($activo
                    ? 'Perfil de Jefe de Carrera reactivado. La cuenta vinculada conserva su estado actual.'
                    : 'Perfil de Jefe de Carrera desactivado. La cuenta vinculada sigue activa y puede iniciar sesión.')
                : ($activo ? 'El perfil ya estaba activo.' : 'El perfil ya estaba inactivo.')
        );
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        return view('admin.jefes-carrera.index', [
            'puedeCrear' => Gate::allows('Jefes de Carrera.crear'),
            'puedeEditar' => Gate::allows('Jefes de Carrera.editar'),
            'puedeEliminar' => Gate::allows('Jefes de Carrera.eliminar'),
        ]);
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::allows('Jefes de Carrera.'.$accion), 403);
    }
}
