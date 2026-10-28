<?php

namespace App\Livewire;

use App\Models\Carrera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Gestion de CUENTAS DE USUARIO del Panel del Administrador.
 *
 * =========================================================================
 * ALTA (etapa actual): la cuenta se crea JUNTO CON su persona academica.
 * =========================================================================
 *
 *   Rol                  Tabla de personas   Vinculo en users
 *   -------------------  ------------------  -------------------
 *   Alumno               alumnos             id_alumno
 *   Docente              docentes            id_docente
 *   Jefe de Carrera      jefes_carrera       id_jefe_carrera
 *   Control Escolar      (no existe)         solo la cuenta
 *
 * Reglas de la creacion de personas:
 *  - Se localiza PRIMERO por el identificador propio de la entidad
 *    (no_control / no_empleado). El nombre NUNCA determina que dos
 *    personas sean la misma.
 *  - Ademas se exige consistencia del correo. Cualquier coincidencia
 *    conflictiva (mismo identificador con otro correo, correo asignado
 *    a otra persona, o imposibilidad de decidir) ABORTA el alta y se
 *    muestra un mensaje claro: jamas se vincula "a ciegas".
 *  - Si la persona ya tiene cuenta, NO se crea otra ni se toca la
 *    existente (validacion en la aplicacion + UNIQUE de la BD).
 *  - Persona y cuenta se crean dentro de UNA transaccion: o se crea
 *    todo, o no se crea nada.
 *
 * =========================================================================
 * EDICION: actualiza la cuenta y el perfil del rol seleccionado.
 * Los vínculos a perfiles anteriores se conservan al cambiar de rol.
 *
 * - Solo puede existir UNA cuenta con rol Administrador: la opcion no
 *   aparece en el selector y ademas el servidor la rechaza aunque la
 *   pagina se manipule (la cuenta existente puede conservar su rol).
 *
 * Fuente oficial del rol: users.FK_id_rol -> roles.id_rol (tabla roles).
 * La columna ENUM legacy users.rol NO se escribe ni se usa en ninguna logica.
 *
 * Permisos (modulo "Usuarios y Roles"):
 *   ver     -> consultar el listado
 *   crear   -> crear cuentas y reactivar cuentas
 *   editar  -> editar cuentas y restablecer contrasenas
 *   eliminar-> desactivar cuentas (no existe borrado fisico; ver informe)
 */
class GestionUsuarios extends Component
{
    /**
     * Roles cuyo alta crea o vincula una persona con tabla propia.
     * "Control Escolar" no esta: no existe entidad de persona para ese rol.
     */
    private const ROLES_CON_PERSONA = ['Alumno', 'Docente', 'Jefe de Carrera'];

    /** De los anteriores, los que ademas exigen carrera. */
    private const ROLES_CON_CARRERA = ['Alumno', 'Jefe de Carrera'];

    /**
     * Roles cuyo alta captura UNA O VARIAS carreras (N:N, tabla
     * intermedia `carrera_docente`). A diferencia de ROLES_CON_CARRERA,
     * aqui NO se escribe ninguna columna en la tabla de personas: la
     * tabla `docentes` no tiene (ni tendra) columna de carrera.
     */
    private const ROLES_CON_MULTI_CARRERA = ['Docente'];

    /** De los anteriores, los que ademas exigen fecha de inicio. */
    private const ROLES_CON_FECHA_INICIO = ['Jefe de Carrera'];

    /**
     * Mapa rol -> entidad de persona.
     *
     *  tabla       tabla fisica
     *  pk          llave primaria de esa tabla
     *  vinculo     columna equivalente en users (todas UNIQUE)
     *  identificador  campo propio que identifica a la persona
     *  etiqueta    nombre legible del identificador (para mensajes)
     *  singular    articulo + rol (para mensajes)
     *  modulo      nombre del modulo donde vive la entidad (para mensajes)
     *  con_carrera / con_fecha_inicio  campos adicionales a capturar
     */
    private const ENTIDADES = [
        'Alumno' => [
            'tabla' => 'alumnos',
            'pk' => 'id_alumno',
            'vinculo' => 'id_alumno',
            'identificador' => 'no_control',
            'etiqueta' => 'número de control',
            'singular' => 'el alumno',
            'modulo' => 'Alumnos',
            'con_carrera' => true,
            'con_fecha_inicio' => false,
        ],
        'Docente' => [
            'tabla' => 'docentes',
            'pk' => 'id_docente',
            'vinculo' => 'id_docente',
            'identificador' => 'no_empleado',
            'etiqueta' => 'número de empleado',
            'singular' => 'el docente',
            'modulo' => 'Docentes',
            'con_carrera' => false,
            'con_fecha_inicio' => false,
        ],
        'Jefe de Carrera' => [
            'tabla' => 'jefes_carrera',
            'pk' => 'id_jefe_carrera',
            'vinculo' => 'id_jefe_carrera',
            'identificador' => 'no_empleado',
            'etiqueta' => 'número de empleado',
            'singular' => 'el jefe de carrera',
            'modulo' => 'Jefes de Carrera',
            'con_carrera' => true,
            'con_fecha_inicio' => true,
        ],
    ];

    // Listado y busqueda
    public $usuarios = [];

    public $search = '';

    public $userCount = 0;

    // Catalogo de roles (fuente oficial: tabla roles). NUNCA incluye
    // "Administrador": solo se permite una cuenta con ese rol.
    public $roles = [];

    public $rolesEdicion = [];

    // Catalogo de carreras: solo se usa en el ALTA de personas que
    // exigen carrera (Alumno y Jefe de Carrera).
    public $carreras = [];

    // Modal Crear / Editar
    public $showModal = false;

    public $editingUserId = null;

    public $nombre = '';

    public $email = '';

    public $password = '';

    public $password_confirmation = '';

    public $rolId = '';

    #[Locked]
    public $rolIdOriginal = '';

    public $rolIdDatosPersona = '';

    public $activo = true;

    // ---- Datos de persona: se usan en alta y en edición para el perfil
    // ---- correspondiente al rol seleccionado.
    public $p_nombre = '';

    public $p_apellido_paterno = '';

    public $p_apellido_materno = '';

    public $no_control = '';

    public $no_empleado = '';

    public $id_carrera = '';

    public $fecha_inicio = '';

    // ---- Docente (N:N con carreras): carreras asociadas a su perfil.
    // IDs de carreras seleccionadas (puede llevar varias).
    public $carreras_ids = [];

    public $carrerasEdicion = [];

    public $carrerasOriginales = [];

    public $datosPersonaPorRol = [];

    public $datosPersonaBase = [];

    public $identificadorPersonaDetectado = '';

    public $carrerasPorAgregar = [];

    public $carrerasPorRetirar = [];

    #[Locked]
    public $carrerasBaseConfirmacion = [];

    // Asignaciones actuales del docente localizado por su numero de
    // empleado, cuando ya tiene carreras distintas de las seleccionadas.
    // null = no hay nada pendiente de confirmar.
    public $docentePreexistente = null;

    // El administrador acepto expresamente REEMPLAZAR las asignaciones
    // existentes. Sin esta marca jamas se sobrescriben.
    public $confirmarCarreras = false;

    // true solo al editar la cuenta que YA tiene el rol Administrador
    // (el rol se muestra en solo lectura; no se puede elegir ni cambiar).
    public $esCuentaAdministrador = false;

    // ---- Proteccion contra el cierre sin guardar (solo EDICION) ----
    // Copia de los valores del formulario al abrir el modal de edicion.
    // Se compara con lo actual para detectar cambios pendientes.
    public $formOriginal = [];

    // true cuando se intento cerrar el modal con cambios sin guardar y
    // aun no se confirma la salida (la vista muestra el aviso).
    public $confirmarCierrePendiente = false;

    // Confirmación explícita antes de aplicar un cambio de rol o retirar
    // carreras que ya estaban asignadas a un docente.
    public $mostrarConfirmacionGuardado = false;

    // Modal de restablecer contrasena
    public $showPasswordModal = false;

    public $passwordTargetId = null;

    public $nuevaPassword = '';

    public $confirmarPassword = '';

    public function mount(): void
    {
        $this->roles = Role::where('activo', 1)
            ->where('nombre', '<>', 'Administrador')
            ->orderBy('id_rol')
            ->get(['id_rol', 'nombre'])
            ->toArray();
        $this->rolesEdicion = $this->roles;

        $this->carreras = Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();

        $this->cargarUsuarios();
    }

    public function cargarUsuarios(): void
    {
        $busqueda = trim((string) $this->search);

        $this->usuarios = User::query()
            ->with(['role', 'docente', 'jefeCarrera', 'alumno'])
            ->when($busqueda !== '', function ($query) use ($busqueda) {
                $query->where(function ($q) use ($busqueda) {
                    $q->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%")
                        ->orWhereHas('role', fn ($qr) => $qr->where('nombre', 'like', "%{$busqueda}%"));
                });
            })
            ->orderBy('id')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'rol_nombre' => $u->role?->nombre ?? 'Sin rol asignado',
                'activo' => (bool) $u->activo,
                'persona_texto' => $this->textoPersona($u),
                'es_cuenta_propia' => $u->id === (int) Auth::id(),
            ])
            ->toArray();

        $this->userCount = count($this->usuarios);
    }

    public function updatedSearch(): void
    {
        $this->cargarUsuarios();
    }

    /**
     * Actualiza el conjunto de campos de persona cuando cambia el rol.
     * En edición se guardan temporalmente los campos del rol anterior para
     * que alternar el selector no descarte capturas que aún no se guardan.
     */
    public function updatedRolId(): void
    {
        if ($this->editingUserId === null) {
            $this->resetValidation();
            $this->docentePreexistente = null;
            $this->confirmarCarreras = false;

            return;
        }

        $usuario = User::findOrFail($this->editingUserId);
        $rolAnteriorFormulario = Role::find((int) $this->rolIdDatosPersona);
        $rolNuevo = Role::find((int) $this->rolId);

        if ($rolAnteriorFormulario !== null) {
            $this->guardarCamposPersonaEnMemoria((string) $rolAnteriorFormulario->id_rol);
        }

        $this->resetValidation();
        $this->docentePreexistente = null;
        $this->confirmarCarreras = false;

        if ($rolNuevo === null) {
            return;
        }

        if ($rolNuevo->nombre === 'Control Escolar') {
            $this->carrerasEdicion = $this->carrerasActivas();
            $this->carrerasOriginales = [];
            $this->datosPersonaBase = [];
            $this->identificadorPersonaDetectado = '';
            $this->rolIdDatosPersona = (string) $rolNuevo->id_rol;

            return;
        }

        $this->cargarCamposPersonaEdicion($usuario, $rolNuevo->nombre, true);
        $this->rolIdDatosPersona = (string) $rolNuevo->id_rol;
    }

    /**
     * Al capturar otro numero de empleado se pierde cualquier aviso previo:
     * las asignaciones que se muestran deben volver a evaluarse contra la
     * persona recien capturada.
     */
    public function updatedNoEmpleado(): void
    {
        $this->confirmarCarreras = false;
        $this->docentePreexistente = $this->detectarDocentePreexistente();
        $this->resetValidation('carreras_ids');

        if ($this->editingUserId !== null && in_array($this->nombreRolElegido(), ['Docente', 'Jefe de Carrera'], true)) {
            $this->prepararPersonaExistenteParaEdicion($this->nombreRolElegido());
        }
    }

    /** Busca y carga un Alumno existente al salir de su número de control. */
    public function updatedNoControl(): void
    {
        if ($this->editingUserId !== null && $this->nombreRolElegido() === 'Alumno') {
            $this->resetValidation('no_control', 'id_carrera');
            $this->prepararPersonaExistenteParaEdicion('Alumno');
        }
    }

    /**
     * Texto descriptivo de la persona vinculada para el listado (solo
     * lectura informativa).
     *
     * El prefijo con el tipo de persona ("Docente ·", "Jefe de Carrera ·",
     * "Alumno ·") solo se muestra mientras COINCIDA con el rol vigente de
     * la cuenta. Si el rol cambio en la edicion (p. ej. Docente -> Alumno)
     * el vinculo de persona se conserva tal cual, pero ya NO se presenta
     * con el rol anterior para no dar la apariencia de que el cambio no
     * se guardo.
     */
    private function textoPersona(User $u): ?string
    {
        $rolActual = $u->role?->nombre;

        if ($u->id_docente !== null && $u->docente) {
            $d = $u->docente;
            $detalle = trim($d->nombre.' '.$d->apellido_paterno.' '.$d->apellido_materno)
                .' · Núm. empleado '.$d->no_empleado;

            return $rolActual === 'Docente' ? 'Docente · '.$detalle : $detalle;
        }

        if ($u->id_jefe_carrera !== null && $u->jefeCarrera) {
            $j = $u->jefeCarrera;
            $detalle = trim($j->nombre.' '.$j->apellido_paterno.' '.$j->apellido_materno)
                .' · Núm. empleado '.$j->no_empleado;

            return $rolActual === 'Jefe de Carrera' ? 'Jefe de Carrera · '.$detalle : $detalle;
        }

        if ($u->id_alumno !== null && $u->alumno) {
            $a = $u->alumno;
            $detalle = trim($a->nombre.' '.$a->apellido_paterno.' '.$a->apellido_materno)
                .' · Núm. control '.$a->no_control;

            return $rolActual === 'Alumno' ? 'Alumno · '.$detalle : $detalle;
        }

        return null;
    }

    /**
     * Abre el formulario de alta de usuario.
     *
     * Tambien escucha el evento `abrir-modal-crear-usuario` que dispara el
     * acceso rapido "Crear Usuario" del Dashboard de Administración General:
     * no se crea un módulo nuevo, se reutiliza este mismo formulario.
     */
    #[On('abrir-modal-crear-usuario')]
    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');

        $this->resetForm();
        $this->showPasswordModal = false;
        $this->showModal = true;
    }

    /** Reutiliza el formulario de alta con el rol Control Escolar seleccionado. */
    #[On('abrir-modal-crear-control-escolar')]
    public function abrirModalCrearControlEscolar(): void
    {
        $this->exigirPermiso('crear');

        $rolControlEscolar = Role::query()
            ->where('nombre', 'Control Escolar')
            ->where('activo', 1)
            ->first();

        if ($rolControlEscolar === null) {
            $this->addError('rolId', 'El rol Control Escolar no está disponible para nuevas cuentas.');

            return;
        }

        $this->abrirModalCrear();
        $this->rolId = (string) $rolControlEscolar->id_rol;
    }

    /** Abre el editor existente únicamente para usuarios del rol Control Escolar. */
    #[On('abrir-modal-editar-control-escolar')]
    public function abrirModalEditarControlEscolar(int $userId): void
    {
        $this->exigirPermiso('editar');

        $usuario = User::with('role')->findOrFail($userId);
        abort_unless($usuario->role?->nombre === 'Control Escolar', 404);

        $this->abrirModalEditar($userId);
    }

    /** Delega la activación/desactivación al método ya existente. */
    #[On('cambiar-estado-control-escolar')]
    public function cambiarEstadoControlEscolar(int $userId): void
    {
        $this->exigirPermiso('editar');

        $usuario = User::with('role')->findOrFail($userId);
        abort_unless($usuario->role?->nombre === 'Control Escolar', 404);

        $this->toggleActivo($userId);
    }

    public function abrirModalEditar($userId): void
    {
        $this->exigirPermiso('editar');

        $usuario = User::findOrFail($userId);

        $this->resetForm();
        $this->editingUserId = $usuario->id;
        $this->nombre = $usuario->name;
        $this->email = $usuario->email;
        $this->rolId = (string) $usuario->FK_id_rol;
        $this->rolIdOriginal = (string) $usuario->FK_id_rol;
        $this->activo = (bool) $usuario->activo;
        $this->esCuentaAdministrador = $usuario->role?->nombre === 'Administrador';
        $this->rolesEdicion = Role::query()
            ->where(function ($query) use ($usuario) {
                $query->where('activo', 1)
                    ->where('nombre', '<>', 'Administrador')
                    ->orWhere('id_rol', $usuario->FK_id_rol);
            })
            ->orderBy('id_rol')
            ->get(['id_rol', 'nombre'])
            ->toArray();

        $this->datosPersonaPorRol = [];
        $this->datosPersonaBase = [];
        $this->identificadorPersonaDetectado = '';
        $this->carrerasOriginales = [];
        $this->carrerasEdicion = $this->carrerasActivas();

        if (! $this->esCuentaAdministrador && isset(self::ENTIDADES[$usuario->role?->nombre ?? ''])) {
            $this->cargarCamposPersonaEdicion($usuario, (string) $usuario->role->nombre);
            $this->rolIdDatosPersona = (string) $usuario->FK_id_rol;
        }

        // Fotografia inicial para detectar cambios sin guardar al cerrar.
        $this->formOriginal = $this->valoresFormularioEdicion();

        $this->showPasswordModal = false;
        $this->showModal = true;
    }

    /**
     * Valores actuales del formulario de edicion, normalizados para poder
     * compararlos con la fotografia tomada al abrir el modal.
     */
    private function valoresFormularioEdicion(): array
    {
        return [
            'nombre' => trim((string) $this->nombre),
            'email' => trim((string) $this->email),
            'rolId' => (string) $this->rolId,
            'activo' => (bool) $this->activo,
            'p_nombre' => trim((string) $this->p_nombre),
            'p_apellido_paterno' => trim((string) $this->p_apellido_paterno),
            'p_apellido_materno' => trim((string) $this->p_apellido_materno),
            'no_control' => trim((string) $this->no_control),
            'no_empleado' => trim((string) $this->no_empleado),
            'id_carrera' => (string) $this->id_carrera,
            'fecha_inicio' => (string) $this->fecha_inicio,
            'carreras_ids' => $this->carrerasDocenteSeleccionadas(),
        ];
    }

    /**
     * Hidrata o restaura los campos de persona del rol seleccionado.
     * Los vínculos que la cuenta ya tenga a otras entidades no se modifican.
     */
    private function cargarCamposPersonaEdicion(User $usuario, string $rolNombre, bool $preservarComunes = false): void
    {
        $entidad = self::ENTIDADES[$rolNombre] ?? null;

        if ($entidad === null) {
            return;
        }

        $keyRol = (string) Role::where('nombre', $rolNombre)->value('id_rol');

        if ($preservarComunes && array_key_exists($keyRol, $this->datosPersonaPorRol)) {
            $this->restaurarCamposPersonaDesdeMemoria($this->datosPersonaPorRol[$keyRol]);
            $this->carrerasEdicion = $this->carrerasParaEdicion($this->carreras_ids);

            return;
        }

        $personaId = $usuario->{$entidad['vinculo']};
        $persona = $personaId !== null
            ? DB::table($entidad['tabla'])->where($entidad['pk'], $personaId)->first()
            : null;

        if ($persona !== null) {
            $this->p_nombre = (string) $persona->nombre;
            $this->p_apellido_paterno = (string) $persona->apellido_paterno;
            $this->p_apellido_materno = (string) ($persona->apellido_materno ?? '');
            $this->email = (string) $usuario->email;

            if ($rolNombre === 'Alumno') {
                $this->no_control = (string) $persona->no_control;
                $this->id_carrera = (string) $persona->id_carrera;
                $this->no_empleado = '';
                $this->fecha_inicio = '';
                $this->carreras_ids = [];
            } elseif ($rolNombre === 'Jefe de Carrera') {
                $this->no_empleado = (string) $persona->no_empleado;
                $this->id_carrera = (string) $persona->id_carrera;
                $this->fecha_inicio = (string) $persona->fecha_inicio;
                $this->no_control = '';
                $this->carreras_ids = [];
            } else {
                $this->no_empleado = (string) $persona->no_empleado;
                $this->no_control = '';
                $this->id_carrera = '';
                $this->fecha_inicio = '';
                $this->carreras_ids = DB::table('carrera_docente')
                    ->where('id_docente', $persona->id_docente)
                    ->orderBy('id_carrera')
                    ->pluck('id_carrera')
                    ->map(fn ($id) => (string) $id)
                    ->all();
            }

            $this->carrerasOriginales = $this->carrerasDocenteSeleccionadas();
            $this->carrerasBaseConfirmacion = $this->carrerasOriginales;
            $this->carrerasEdicion = $this->carrerasParaEdicion(
                $rolNombre === 'Docente' ? $this->carreras_ids : [$this->id_carrera]
            );
            $this->identificadorPersonaDetectado = '';
            $this->datosPersonaBase = [];
        } else {
            $rolOrigen = Role::find((int) $this->rolIdOriginal)?->nombre;
            $empleadoCompatible = in_array($rolOrigen, ['Docente', 'Jefe de Carrera'], true)
                && in_array($rolNombre, ['Docente', 'Jefe de Carrera'], true);

            if (! $preservarComunes) {
                $this->p_nombre = '';
                $this->p_apellido_paterno = '';
                $this->p_apellido_materno = '';
            }

            $this->no_control = '';
            $this->no_empleado = $empleadoCompatible ? trim((string) $this->no_empleado) : '';
            $this->id_carrera = '';
            $this->fecha_inicio = '';
            $this->carreras_ids = [];
            $this->carrerasOriginales = [];
            $this->carrerasBaseConfirmacion = [];
            $this->carrerasEdicion = $this->carrerasActivas();
            $this->identificadorPersonaDetectado = '';
            $this->datosPersonaBase = [
                'p_nombre' => $this->p_nombre,
                'p_apellido_paterno' => $this->p_apellido_paterno,
                'p_apellido_materno' => $this->p_apellido_materno,
            ];
        }

        $this->guardarCamposPersonaEnMemoria($keyRol);
    }

    /**
     * Si un identificador conduce a una persona existente no vinculada a otra
     * cuenta, carga sus datos antes de permitir editarla o reutilizarla.
     */
    private function prepararPersonaExistenteParaEdicion(string $rolNombre): void
    {
        $entidad = self::ENTIDADES[$rolNombre] ?? null;

        if ($entidad === null) {
            return;
        }

        $usuario = User::findOrFail($this->editingUserId);

        if ($usuario->{$entidad['vinculo']} !== null) {
            return;
        }

        $identificador = trim((string) $this->{$entidad['identificador']});

        if ($identificador === '') {
            return;
        }

        if ($this->identificadorPersonaDetectado !== ''
            && $this->identificadorPersonaDetectado !== $identificador) {
            $this->p_nombre = (string) ($this->datosPersonaBase['p_nombre'] ?? '');
            $this->p_apellido_paterno = (string) ($this->datosPersonaBase['p_apellido_paterno'] ?? '');
            $this->p_apellido_materno = (string) ($this->datosPersonaBase['p_apellido_materno'] ?? '');
            $this->id_carrera = '';
            $this->fecha_inicio = '';
            $this->carreras_ids = [];
            $this->carrerasOriginales = [];
            $this->carrerasBaseConfirmacion = [];
            $this->docentePreexistente = null;
            $this->identificadorPersonaDetectado = '';
            $this->carrerasEdicion = $this->carrerasActivas();
        }

        $persona = DB::table($entidad['tabla'])
            ->where($entidad['identificador'], $identificador)
            ->first();

        if ($persona === null
            || mb_strtolower(trim((string) $persona->email)) !== mb_strtolower(trim((string) $this->email))) {
            return;
        }

        $vinculadaAOtraCuenta = DB::table('users')
            ->where($entidad['vinculo'], $persona->{$entidad['pk']})
            ->where('id', '<>', $usuario->id)
            ->exists();

        if ($vinculadaAOtraCuenta) {
            return;
        }

        $this->carrerasOriginales = [];
        $this->carrerasBaseConfirmacion = [];
        $this->p_nombre = (string) $persona->nombre;
        $this->p_apellido_paterno = (string) $persona->apellido_paterno;
        $this->p_apellido_materno = (string) ($persona->apellido_materno ?? '');
        $this->identificadorPersonaDetectado = $identificador;

        if ($rolNombre === 'Alumno') {
            $this->id_carrera = (string) $persona->id_carrera;
            $this->carreras_ids = [];
            $idsCarrera = [$persona->id_carrera];
        } elseif ($rolNombre === 'Jefe de Carrera') {
            $this->id_carrera = (string) $persona->id_carrera;
            $this->fecha_inicio = (string) $persona->fecha_inicio;
            $this->carreras_ids = [];
            $idsCarrera = [$persona->id_carrera];
        } else {
            $this->carreras_ids = DB::table('carrera_docente')
                ->where('id_docente', $persona->id_docente)
                ->orderBy('id_carrera')
                ->pluck('id_carrera')
                ->map(fn ($id) => (string) $id)
                ->all();
            $this->carrerasOriginales = $this->carrerasDocenteSeleccionadas();
            $this->carrerasBaseConfirmacion = $this->carrerasOriginales;
            $this->docentePreexistente = $this->detectarDocentePreexistente();
            $idsCarrera = $this->carreras_ids;
        }

        $this->carrerasEdicion = $this->carrerasParaEdicion($idsCarrera);
        $this->guardarCamposPersonaEnMemoria((string) $this->rolId);
    }

    /** Conserva la captura pendiente del formulario del rol indicado. */
    private function guardarCamposPersonaEnMemoria(string $rolId): void
    {
        $this->datosPersonaPorRol[$rolId] = [
            'p_nombre' => $this->p_nombre,
            'p_apellido_paterno' => $this->p_apellido_paterno,
            'p_apellido_materno' => $this->p_apellido_materno,
            'no_control' => $this->no_control,
            'no_empleado' => $this->no_empleado,
            'id_carrera' => $this->id_carrera,
            'fecha_inicio' => $this->fecha_inicio,
            'carreras_ids' => $this->carreras_ids,
            'carrerasOriginales' => $this->carrerasOriginales,
            'carrerasBaseConfirmacion' => $this->carrerasBaseConfirmacion,
            'datosPersonaBase' => $this->datosPersonaBase,
            'identificadorPersonaDetectado' => $this->identificadorPersonaDetectado,
        ];
    }

    /** Restaura la captura pendiente de un rol. */
    private function restaurarCamposPersonaDesdeMemoria(array $datos): void
    {
        foreach ([
            'p_nombre', 'p_apellido_paterno', 'p_apellido_materno', 'no_control',
            'no_empleado', 'id_carrera', 'fecha_inicio', 'carreras_ids', 'carrerasOriginales',
            'carrerasBaseConfirmacion',
            'datosPersonaBase', 'identificadorPersonaDetectado',
        ] as $campo) {
            if (array_key_exists($campo, $datos)) {
                $this->{$campo} = $datos[$campo];
            }
        }
    }

    /** Lista carreras activas más las que ya están asociadas al perfil editado. */
    private function carrerasParaEdicion(array $idsSeleccionados = []): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsSeleccionados))));

        return Carrera::query()
            ->where(function ($query) use ($ids) {
                $query->where('activo', 1);

                if ($ids !== []) {
                    $query->orWhereIn('id_carrera', $ids);
                }
            })
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();
    }

    /** Devuelve el catálogo activo que se usa en Nuevo usuario. */
    private function carrerasActivas(): array
    {
        return Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre', 'activo'])
            ->toArray();
    }

    /**
     * Indica si el formulario de EDICION tiene cambios respecto de lo que
     * se cargo al abrir el modal. En ALTA siempre es false: este aviso
     * protege unicamente la edicion de cuentas existentes.
     */
    public function hayCambiosSinGuardar(): bool
    {
        if ($this->editingUserId === null || $this->formOriginal === []) {
            return false;
        }

        return $this->valoresFormularioEdicion() !== $this->formOriginal;
    }

    /**
     * Cierre del modal (X, Cancelar o clic en el fondo). Si hay cambios
     * sin guardar en EDICION, en lugar de cerrar publica el aviso; si no,
     * cierra de inmediato igual que antes.
     */
    public function cerrarModal(): void
    {
        if ($this->hayCambiosSinGuardar()) {
            $this->confirmarCierrePendiente = true;

            return;
        }

        $this->showModal = false;
        $this->confirmarCierrePendiente = false;
    }

    /** El administrador decidio continuar editando: se retira el aviso. */
    public function cancelarCierreConfirmado(): void
    {
        $this->confirmarCierrePendiente = false;
    }

    /** Cierre confirmado: se descartan los cambios y se limpia el form. */
    public function cerrarModalSinGuardar(): void
    {
        $this->confirmarCierrePendiente = false;
        $this->showModal = false;
        $this->resetForm();
    }

    public function guardar(): void
    {
        $this->procesarGuardado(false);
    }

    /** Confirmación explícita solicitada al cambiar de rol o retirar carreras. */
    public function confirmarGuardadoPendiente(): void
    {
        $this->mostrarConfirmacionGuardado = false;
        $this->procesarGuardado(true);
    }

    /** Cancela la confirmación sin descartar lo capturado en el formulario. */
    public function cancelarGuardadoPendiente(): void
    {
        $this->mostrarConfirmacionGuardado = false;
    }

    private function procesarGuardado(bool $confirmado): void
    {
        $this->exigirPermiso($this->editingUserId ? 'editar' : 'crear');

        // Limpia errores de un intento anterior (incluidos los conflictos de
        // persona que se agregan con addError y no pertenecen a las reglas).
        $this->resetValidation();

        $esCreacion = $this->editingUserId === null;

        // Se resuelve ANTES de validar porque el set de reglas depende del
        // rol elegido. Si el rol no existe, la regla "exists" lo rechaza.
        $rolEnFormulario = ($this->rolId !== '' && $this->rolId !== null)
            ? Role::find((int) $this->rolId)
            : null;

        $rolNombre = $rolEnFormulario?->nombre ?? '';

        // ¿El ALTA implica crear o vincular una persona academica?
        $rolConEntidad = $rolEnFormulario !== null
            && isset(self::ENTIDADES[$rolNombre]);

        $creaPersona = $esCreacion
            && $rolEnFormulario !== null
            && in_array($rolNombre, self::ROLES_CON_PERSONA, true);

        $reglas = [
            'email' => ['required', 'email', 'max:'.($rolConEntidad ? '100' : '255'), Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'rolId' => ['required', 'integer', Rule::exists('roles', 'id_rol')],
            'activo' => ['required', 'boolean'],
        ];

        // La contrasena solo se define al CREAR la cuenta; en edicion se usa
        // la accion separada de restablecer contrasena.
        if ($esCreacion) {
            $reglas['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }

        if ($creaPersona || ($this->editingUserId !== null && $rolConEntidad)) {
            $reglas = array_merge($reglas, $this->reglasPersona($rolNombre));
        } else {
            // Sin persona (edicion, o alta de Control Escolar): se captura
            // el nombre completo de la cuenta, igual que hasta ahora.
            $reglas['nombre'] = ['required', 'string', 'min:3', 'max:255'];
        }

        $mensajes = $this->mensajesValidacion();
        $mensajes['email.max'] = $rolConEntidad
            ? 'El correo electrónico de la persona no puede superar los 100 caracteres.'
            : 'El correo electrónico no puede superar los 255 caracteres.';

        $this->validate($reglas, $mensajes);

        $usuario = $this->editingUserId !== null ? User::findOrFail($this->editingUserId) : null;

        if ($usuario !== null && (int) $usuario->FK_id_rol !== (int) $this->rolIdOriginal) {
            $this->addError('rolId', 'El rol de esta cuenta cambió desde que abriste el formulario. Vuelve a abrirla para continuar.');

            return;
        }

        if ($usuario !== null && $rolConEntidad) {
            $this->agregarReglasUnicidadPersonaEdicion($reglas, $usuario, $rolNombre);
            $this->validate($reglas, $mensajes);

            if (! $this->validarCarrerasEdicion($usuario, $rolNombre)) {
                return;
            }

            if (! $this->validarPersonaDestino($usuario, $rolNombre)) {
                return;
            }
        }

        // Proteccion de la cuenta autenticada (Etapa 3, punto 12):
        // no puede cambiar su propio rol ni desactivarse desde la edicion.
        if ($usuario !== null && $usuario->id === (int) Auth::id()) {
            if ((int) $this->rolId !== (int) $usuario->FK_id_rol) {
                $this->addError('rolId', 'No puedes cambiar el rol de la cuenta con la que has iniciado sesión.');

                return;
            }

            if (! (bool) $this->activo) {
                $this->addError('activo', 'No puedes desactivar tu propia cuenta.');

                return;
            }
        }

        // RESTRICCION INTERNA: solo puede existir UN Administrador.
        // El rol "Administrador" no aparece en el selector, pero aunque la
        // pagina se manipule y llegue aqui, solo se acepta si la cuenta en
        // edicion YA lo tiene (valor sin cambios); nunca se lo asigna a
        // otra cuenta ni a una cuenta nueva.
        $rol = Role::findOrFail((int) $this->rolId);

        if (! $rol->activo
            && ($usuario === null || (int) $usuario->FK_id_rol !== (int) $rol->id_rol)) {
            $this->addError('rolId', 'No se puede asignar un rol inactivo.');

            return;
        }

        if ($usuario !== null && $usuario->esAdministrador() && (int) $usuario->FK_id_rol !== (int) $rol->id_rol) {
            $this->addError('rolId', 'El rol de la cuenta Administrador no puede cambiarse.');

            return;
        }

        if ($rol->nombre === 'Administrador') {
            $esLaCuentaAdministradora = $usuario !== null
                && (int) $usuario->FK_id_rol === (int) $rol->id_rol;

            if (! $esLaCuentaAdministradora) {
                $this->addError(
                    'rolId',
                    'Solo se permite una cuenta Administrador en el sistema: este rol no puede asignarse.'
                );

                return;
            }
        }

        $isEditing = $this->editingUserId !== null;

        if ($isEditing) {
            $cambioRol = (int) $usuario->FK_id_rol !== (int) $rol->id_rol;
            $carrerasActualesDestino = $this->carrerasActualesDestinoDocente($usuario, $rol);
            $carrerasSeleccionadas = $rol->nombre === 'Docente'
                ? $this->carrerasDocenteSeleccionadas()
                : [];
            [$carrerasAgregadas, $carrerasRetiradas] = $this->cambiosDeCarrerasDocente(
                $carrerasActualesDestino,
                $carrerasSeleccionadas
            );
            $cambioCarreras = $carrerasAgregadas !== [] || $carrerasRetiradas !== [];

            if ($confirmado
                && $cambioCarreras
                && $carrerasActualesDestino !== $this->carrerasBaseConfirmacion) {
                $this->addError('carreras_ids', 'Las carreras del perfil cambiaron desde que se mostró la confirmación. Revisa la selección y vuelve a guardar.');

                return;
            }

            if (($cambioRol || $cambioCarreras) && ! $confirmado) {
                $this->carrerasPorAgregar = $carrerasAgregadas;
                $this->carrerasPorRetirar = $carrerasRetiradas;
                $this->carrerasBaseConfirmacion = $carrerasActualesDestino;
                $this->mostrarConfirmacionGuardado = true;

                return;
            }

            if (! $this->guardarEdicionEnTransaccion($usuario, $rol, $confirmado)) {
                return;
            }

            $mensajeExito = 'Usuario actualizado correctamente.';
        } else {
            // =========================================================
            // ALTA: crea la persona (o la localiza) y la vincula.
            // Devuelve null si algo fallo: el error ya quedo en la vista
            // y el modal permanece abierto (no se cerro ni se recargo).
            // =========================================================
            $mensajeExito = $this->crearCuentaNueva($rol);

            if ($mensajeExito === null) {
                return;
            }
        }

        $this->showModal = false;
        $this->mostrarConfirmacionGuardado = false;
        $this->resetForm();
        $this->cargarUsuarios();
        $this->dispatch('actualizar-personal-control-escolar');

        session()->flash('success', $mensajeExito);
    }

    /**
     * Agrega unicidad para el registro persona que se actualizará o vinculará.
     * Se excluye únicamente el registro destino identificado por su llave real.
     */
    private function agregarReglasUnicidadPersonaEdicion(array &$reglas, User $usuario, string $rolNombre): void
    {
        $entidad = self::ENTIDADES[$rolNombre];
        $personaId = $usuario->{$entidad['vinculo']}
            ?? DB::table($entidad['tabla'])
                ->where($entidad['identificador'], trim((string) $this->{$entidad['identificador']}))
                ->value($entidad['pk']);

        $reglaIdentificador = Rule::unique($entidad['tabla'], $entidad['identificador']);
        $reglaCorreo = Rule::unique($entidad['tabla'], 'email');

        if ($personaId !== null) {
            $reglaIdentificador->ignore($personaId, $entidad['pk']);
            $reglaCorreo->ignore($personaId, $entidad['pk']);
        }

        $reglas[$entidad['identificador']][] = $reglaIdentificador;
        $reglas['email'][] = $reglaCorreo;
    }

    /**
     * Comprueba que el perfil destino sea inequívoco y no esté vinculado a
     * otra cuenta. Los perfiles previos de esta cuenta pueden compartir el
     * correo y se conservan; un perfil ajeno con ese correo bloquea el cambio.
     */
    private function validarPersonaDestino(User $usuario, string $rolNombre): bool
    {
        $entidad = self::ENTIDADES[$rolNombre];
        $identificador = trim((string) $this->{$entidad['identificador']});
        $personaVinculadaId = $usuario->{$entidad['vinculo']};
        $personaVinculada = $personaVinculadaId !== null
            ? DB::table($entidad['tabla'])->where($entidad['pk'], $personaVinculadaId)->first()
            : null;
        $personaPorIdentificador = DB::table($entidad['tabla'])
            ->where($entidad['identificador'], $identificador)
            ->first();

        if ($personaVinculada !== null
            && $personaPorIdentificador !== null
            && (int) $personaVinculada->{$entidad['pk']} !== (int) $personaPorIdentificador->{$entidad['pk']}) {
            $this->addError(
                $entidad['identificador'],
                'Ese identificador pertenece a otra persona. No se reemplazó el vínculo que ya tiene esta cuenta.'
            );

            return false;
        }

        $personaDestino = $personaVinculada ?? $personaPorIdentificador;

        if ($personaDestino !== null && $personaVinculada === null) {
            if (mb_strtolower(trim((string) $personaDestino->email)) !== mb_strtolower(trim((string) $this->email))) {
                $this->addError(
                    $entidad['identificador'],
                    'El identificador ya existe con otro correo. No se vinculó ni modificó esa persona.'
                );

                return false;
            }

            $otraCuenta = DB::table('users')
                ->where($entidad['vinculo'], $personaDestino->{$entidad['pk']})
                ->where('id', '<>', $usuario->id)
                ->exists();

            if ($otraCuenta) {
                $this->addError(
                    $entidad['identificador'],
                    'Esa persona ya está vinculada a otra cuenta. No se transfirió el vínculo.'
                );

                return false;
            }
        }

        if ($personaDestino === null) {
            $personaConCorreo = DB::table($entidad['tabla'])
                ->where('email', trim((string) $this->email))
                ->first();

            if ($personaConCorreo !== null) {
                $this->addError(
                    'email',
                    'Ese correo ya pertenece a otra persona de este tipo. No se creó un registro duplicado.'
                );

                return false;
            }
        }

        foreach (self::ENTIDADES as $otraEntidad) {
            if ($otraEntidad['tabla'] === $entidad['tabla']) {
                continue;
            }

            $personaConCorreo = DB::table($otraEntidad['tabla'])
                ->where('email', trim((string) $this->email))
                ->first();

            if ($personaConCorreo === null) {
                continue;
            }

            $cuentaVinculada = DB::table('users')
                ->where($otraEntidad['vinculo'], $personaConCorreo->{$otraEntidad['pk']})
                ->value('id');

            if ((int) $cuentaVinculada !== (int) $usuario->id) {
                $this->addError(
                    'email',
                    'Ese correo ya está asociado a otra persona y cuenta. No se duplicó ni se vinculó ese registro.'
                );

                return false;
            }
        }

        return true;
    }

    /** Evita asignar una carrera inactiva que no pertenezca ya al perfil destino. */
    private function validarCarrerasEdicion(User $usuario, string $rolNombre): bool
    {
        $entidad = self::ENTIDADES[$rolNombre];

        if ($entidad['con_carrera']) {
            $carreraId = (int) $this->id_carrera;
            $carreraActiva = Carrera::query()->whereKey($carreraId)->where('activo', 1)->exists();

            if ($carreraActiva) {
                return true;
            }

            $personaId = $usuario->{$entidad['vinculo']}
                ?? DB::table($entidad['tabla'])
                    ->where($entidad['identificador'], trim((string) $this->{$entidad['identificador']}))
                    ->value($entidad['pk']);
            $carreraActual = $personaId !== null
                ? DB::table($entidad['tabla'])->where($entidad['pk'], $personaId)->value('id_carrera')
                : null;

            if ((int) $carreraActual === $carreraId) {
                return true;
            }

            $this->addError('id_carrera', 'La carrera seleccionada está inactiva y no pertenece al perfil existente.');

            return false;
        }

        if ($rolNombre !== 'Docente') {
            return true;
        }

        $docenteId = $usuario->id_docente
            ?? DB::table('docentes')
                ->where('no_empleado', trim((string) $this->no_empleado))
                ->value('id_docente');
        $actuales = $docenteId !== null
            ? $this->carrerasActualesDelDocente((int) $docenteId)
            : [];

        foreach ($this->carrerasDocenteSeleccionadas() as $carreraId) {
            $carreraActiva = Carrera::query()->whereKey($carreraId)->where('activo', 1)->exists();

            if (! $carreraActiva && ! in_array($carreraId, $actuales, true)) {
                $this->addError('carreras_ids', 'No se puede agregar una carrera inactiva al perfil Docente.');

                return false;
            }
        }

        return true;
    }

    /** Carreras actualmente asignadas al perfil Docente destino. */
    private function carrerasActualesDestinoDocente(User $usuario, Role $rol): array
    {
        if ($rol->nombre !== 'Docente') {
            return [];
        }

        $docenteId = $usuario->id_docente;

        if ($docenteId === null) {
            $docenteId = DB::table('docentes')
                ->where('no_empleado', trim((string) $this->no_empleado))
                ->value('id_docente');
        }

        return $docenteId !== null
            ? $this->carrerasActualesDelDocente((int) $docenteId)
            : [];
    }

    /** Devuelve las carreras que se agregarían y retirarían del perfil Docente destino. */
    private function cambiosDeCarrerasDocente(array $actuales, array $seleccionadas): array
    {
        return [
            array_values(array_diff($seleccionadas, $actuales)),
            array_values(array_diff($actuales, $seleccionadas)),
        ];
    }

    /** Actualiza o crea el perfil destino y la cuenta en una transacción única. */
    private function guardarEdicionEnTransaccion(User $usuario, Role $rol, bool $confirmado): bool
    {
        $entidad = self::ENTIDADES[$rol->nombre] ?? null;

        try {
            $guardado = DB::transaction(function () use ($usuario, $rol, $entidad, $confirmado): bool {
                $cuenta = User::query()->whereKey($usuario->id)->lockForUpdate()->firstOrFail();

                if ((int) $cuenta->FK_id_rol !== (int) $usuario->FK_id_rol) {
                    $this->addError('rolId', 'El rol de esta cuenta cambió mientras la editabas. Vuelve a abrir el formulario.');

                    return false;
                }

                if ($entidad !== null && ! $this->validarPersonaDestino($cuenta, $rol->nombre)) {
                    return false;
                }

                if ($rol->nombre === 'Docente') {
                    $docenteIdActual = $cuenta->id_docente
                        ?? DB::table('docentes')
                            ->where('no_empleado', trim((string) $this->no_empleado))
                            ->value('id_docente');

                    if ($docenteIdActual !== null) {
                        DB::table('docentes')
                            ->where('id_docente', $docenteIdActual)
                            ->lockForUpdate()
                            ->first();
                    }

                    $carrerasActuales = $docenteIdActual !== null
                        ? $this->carrerasActualesDelDocente((int) $docenteIdActual, true)
                        : [];
                    $carrerasSeleccionadas = $this->carrerasDocenteSeleccionadas();

                    if ($carrerasActuales !== $this->carrerasBaseConfirmacion) {
                        $this->addError('carreras_ids', 'Las carreras del perfil cambiaron mientras lo editabas. Revisa la selección y vuelve a guardar.');

                        return false;
                    }

                    if ($carrerasActuales !== $carrerasSeleccionadas && ! $confirmado) {
                        $this->addError('carreras_ids', 'Confirma las carreras que se agregarán o retirarán antes de guardar.');

                        return false;
                    }
                }

                $datosCuenta = [
                    'email' => trim((string) $this->email),
                    'activo' => (bool) $this->activo,
                    'FK_id_rol' => (int) $rol->id_rol,
                ];

                if ($entidad === null) {
                    $datosCuenta['name'] = $cuenta->esAdministrador()
                        ? $cuenta->name
                        : trim((string) $this->nombre);
                } else {
                    $personaId = $this->guardarPersonaDestino($cuenta, $entidad);
                    $datosCuenta['name'] = $this->nombreCompuesto();
                    $datosCuenta[$entidad['vinculo']] = $personaId;

                    if ($rol->nombre === 'Docente') {
                        $actuales = $this->carrerasActualesDelDocente($personaId);

                        if ($actuales !== $this->carrerasDocenteSeleccionadas()) {
                            $this->guardarCarrerasDelDocente($personaId);
                        }
                    }
                }

                // Los demás vínculos de persona se omiten intencionalmente:
                // cambiar el rol no elimina ni reemplaza relaciones anteriores.
                $cuenta->fill($datosCuenta)->save();

                return true;
            });
        } catch (UniqueConstraintViolationException $e) {
            $campo = isset(self::ENTIDADES[$rol->nombre])
                ? self::ENTIDADES[$rol->nombre]['identificador']
                : 'email';
            $this->addError($campo, 'El dato ya está registrado en otra cuenta o persona. No se guardó ningún cambio.');

            return false;
        } catch (\Throwable $e) {
            report($e);
            $this->addError('email', 'No se pudo actualizar la cuenta y su perfil. La operación se revirtió sin cambios parciales.');

            return false;
        }

        return $guardado;
    }

    /** Actualiza el perfil existente o crea uno nuevo cuando no existe coincidencia válida. */
    private function guardarPersonaDestino(User $usuario, array $entidad): int
    {
        $personaIdVinculada = $usuario->{$entidad['vinculo']};
        $identificador = trim((string) $this->{$entidad['identificador']});
        $persona = $personaIdVinculada !== null
            ? DB::table($entidad['tabla'])->where($entidad['pk'], $personaIdVinculada)->lockForUpdate()->first()
            : DB::table($entidad['tabla'])->where($entidad['identificador'], $identificador)->lockForUpdate()->first();

        $datos = $this->datosNuevaPersona($entidad);
        unset($datos['activo'], $datos['created_at']);

        if ($persona !== null) {
            DB::table($entidad['tabla'])
                ->where($entidad['pk'], $persona->{$entidad['pk']})
                ->update($datos);

            return (int) $persona->{$entidad['pk']};
        }

        $datos['activo'] = 1;
        $datos['created_at'] = now();
        $datos['updated_at'] = now();

        return (int) DB::table($entidad['tabla'])->insertGetId($datos, $entidad['pk']);
    }

    /* ================================================================
       ALTA DE CUENTA + PERSONA
    ================================================================ */

    /**
     * Reglas de campos para personas académicas. En alta la identificación
     * consistente se resuelve en crearCuentaConPersona(); en edición las
     * reglas de unicidad excluyen el perfil destino cuando corresponde.
     */
    private function reglasPersona(string $rolNombre): array
    {
        $reglas = [
            'p_nombre' => ['required', 'string', 'min:2', 'max:50'],
            'p_apellido_paterno' => ['required', 'string', 'min:2', 'max:50'],
            'p_apellido_materno' => ['nullable', 'string', 'max:50'],
        ];

        // La misma direccion de correo se guarda TAMBIEN en la persona, y
        // alumnos.email / docentes.email / jefes_carrera.email son
        // varchar(100). El servidor MySQL corre en modo NO estricto y
        // truncaria en silencio, dejando a la cuenta y a la persona con
        // correos distintos: se limita aqui para que no ocurra.
        $reglas['email'] = [
            'required',
            'email',
            'max:100',
            Rule::unique('users', 'email')->ignore($this->editingUserId),
        ];

        if ($rolNombre === 'Alumno') {
            $reglas['no_control'] = ['required', 'string', 'min:3', 'max:20'];
            $reglas['id_carrera'] = ['required', 'integer', Rule::exists('carreras', 'id_carrera')];
        } else {
            // Docente y Jefe de Carrera.
            $reglas['no_empleado'] = ['required', 'string', 'min:1', 'max:20'];
        }

        if (in_array($rolNombre, self::ROLES_CON_CARRERA, true)) {
            $reglas['id_carrera'] = ['required', 'integer', Rule::exists('carreras', 'id_carrera')];
        }

        // Docente: UNA O VARIAS carreras, guardadas en la tabla intermedia
        // carrera_docente. Nunca se escribe una columna en `docentes`.
        if (in_array($rolNombre, self::ROLES_CON_MULTI_CARRERA, true)) {
            $conservarDocenteSinCarreras = $this->editingUserId !== null
                && $this->docenteDestinoExistenteSinCarreras();

            $reglas['carreras_ids'] = $conservarDocenteSinCarreras
                ? ['nullable', 'array']
                : ['required', 'array', 'min:1'];
            $reglas['carreras_ids.*'] = ['integer', Rule::exists('carreras', 'id_carrera')];
        }

        if (in_array($rolNombre, self::ROLES_CON_FECHA_INICIO, true)) {
            $reglas['fecha_inicio'] = ['required', 'date'];
        }

        return $reglas;
    }

    /**
     * Detecta un docente existente sin asignaciones formales. Al editar o
     * vincularlo se permite conservar ese estado sin inventar una carrera.
     */
    private function docenteDestinoExistenteSinCarreras(): bool
    {
        $usuario = User::find($this->editingUserId);
        $docenteId = $usuario?->id_docente;

        if ($docenteId === null && trim((string) $this->no_empleado) !== '') {
            $docenteId = DB::table('docentes')
                ->where('no_empleado', trim((string) $this->no_empleado))
                ->value('id_docente');
        }

        return $docenteId !== null
            && ! DB::table('carrera_docente')->where('id_docente', $docenteId)->exists();
    }

    /**
     * Punto de entrada del ALTA. Devuelve el mensaje de exito o null si
     * fallo (en cuyo caso el error ya esta en el bag de la vista).
     */
    private function crearCuentaNueva(Role $rol): ?string
    {
        $entidad = self::ENTIDADES[$rol->nombre] ?? null;

        // ---- Control Escolar (y cualquier rol sin entidad propia) ----
        // Solo se crea la cuenta. No existe tabla de personas para este
        // rol y NO se crea ninguna.
        if ($entidad === null) {
            try {
                DB::transaction(function () use ($rol) {
                    User::create([
                        'name' => trim($this->nombre),
                        'email' => trim($this->email),
                        // El cast 'hashed' del modelo convierte el texto
                        // plano a hash (bcrypt).
                        'password' => $this->password,
                        'activo' => (bool) $this->activo,
                        'FK_id_rol' => (int) $rol->id_rol,
                    ]);
                });
            } catch (\Throwable $e) {
                report($e);

                $this->addError('email', 'No se pudo crear la cuenta. No se guardó ningún dato.');

                return null;
            }

            return 'Cuenta creada correctamente.';
        }

        return $this->crearCuentaConPersona($rol, $entidad);
    }

    /**
     * Detecta (o crea) la persona del rol elegido y crea la cuenta
     * vinculada, todo dentro de UNA transaccion.
     *
     * Fase 1  SOLO LECTURA: localiza por identificador propio y comprueba
     *         consistencia del correo + que la persona no tenga ya cuenta.
     *         Si hay conflicto se aborta AQUI, sin escribir nada.
     * Fase 2  ESCRITURA: crea la persona si hace falta y la cuenta con su
     *         columna de vinculo, en la misma transaccion.
     */
    private function crearCuentaConPersona(Role $rol, array $entidad): ?string
    {
        $tabla = $entidad['tabla'];
        $campoId = $entidad['identificador'];
        $etiqueta = $entidad['etiqueta'];
        $singular = $entidad['singular'];

        $identificador = trim((string) $this->{$campoId});
        $email = trim((string) $this->email);

        // ================= Fase 1: deteccion (sin escrituras) =================

        $persona = DB::table($tabla)->where($campoId, $identificador)->first();

        if ($persona !== null) {
            // Existe por su identificador propio: el correo debe ser
            // consistente para poder afirmar que es la MISMA persona.
            if (mb_strtolower(trim((string) $persona->email)) !== mb_strtolower($email)) {
                $this->addError($campoId, sprintf(
                    'Ya existe %s %s con el %s "%s", pero su correo es %s y no coincide con el capturado. '
                    .'Por seguridad NO se creó la cuenta: confirma primero si se trata de la misma persona.',
                    $singular,
                    $this->nombreDeFila($persona),
                    $etiqueta,
                    $identificador,
                    $persona->email
                ));

                return null;
            }
        } else {
            // No esta por identificador: asegurarnos de que el correo no
            // pertenezca ya a OTRA persona de esta misma entidad.
            $porCorreo = DB::table($tabla)->where('email', $email)->first();

            if ($porCorreo !== null) {
                $this->addError('email', sprintf(
                    'El correo "%s" ya está registrado para %s %s con el %s "%s". '
                    .'No se pudo determinar que sea la misma persona, por lo que NO se creó la cuenta.',
                    $email,
                    $singular,
                    $this->nombreDeFila($porCorreo),
                    $etiqueta,
                    $porCorreo->{$campoId}
                ));

                return null;
            }

            // ... ni a una persona de OTRA entidad (Alumnos / Docentes /
            // Jefes de Carrera): ahi tampoco es posible decidirlo con
            // seguridad y no se debe vincular a ciegas.
            $enOtraEntidad = $this->personaEnOtraEntidad($entidad, $email);

            if ($enOtraEntidad !== null) {
                $this->addError('email', sprintf(
                    'El correo "%s" ya está registrado en el módulo de %s para %s %s (%s "%s"). '
                    .'No es posible confirmar que sea la misma persona, por lo que NO se creó la cuenta.',
                    $email,
                    $enOtraEntidad['modulo'],
                    $enOtraEntidad['singular'],
                    $this->nombreDeFila($enOtraEntidad['fila']),
                    $enOtraEntidad['etiqueta'],
                    $enOtraEntidad['fila']->{$enOtraEntidad['identificador']}
                ));

                return null;
            }
        }

        // ¿La persona YA tiene una cuenta vinculada?
        // (La columna en users es UNIQUE, pero se valida aqui para mostrar
        //  un mensaje comprensible en lugar de una excepcion SQL.)
        if ($persona !== null) {
            $cuenta = DB::table('users')
                ->where($entidad['vinculo'], $persona->{$entidad['pk']})
                ->first();

            if ($cuenta !== null) {
                $this->addError($campoId, sprintf(
                    '%s %s ya tiene una cuenta de usuario (%s). '
                    .'No se creó otra cuenta ni se modificó la existente.',
                    $singular,
                    $this->nombreDeFila($persona),
                    $cuenta->email
                ));

                return null;
            }

            // Un docente existente puede traer carreras asignadas: jamas se
            // reemplazan sin que el administrador lo confirme expresamente.
            if (! $this->carrerasDeDocenteConfirmadas($persona)) {
                return null;
            }
        }

        // ================= Fase 2: escritura atomica =================

        try {
            DB::transaction(function () use ($rol, $entidad, $persona) {
                $personaId = $persona !== null ? (int) $persona->{$entidad['pk']} : null;

                if ($personaId === null) {
                    $personaId = (int) DB::table($entidad['tabla'])->insertGetId(
                        $this->datosNuevaPersona($entidad),
                        $entidad['pk']
                    );
                }

                // Cast 'hashed' del modelo: bcrypt. El vinculo se guarda en
                // la MISMA transaccion que la persona.
                User::create([
                    'name' => $this->nombreCompuesto(),
                    'email' => trim($this->email),
                    'password' => $this->password,
                    'activo' => (bool) $this->activo,
                    'FK_id_rol' => (int) $rol->id_rol,
                    $entidad['vinculo'] => $personaId,
                ]);

                // Carreras del docente (N:N) en la MISMA transaccion:
                // o se crea la cuenta con sus carreras, o no se crea nada.
                if (in_array($rol->nombre, self::ROLES_CON_MULTI_CARRERA, true)) {
                    $this->guardarCarrerasDelDocente($personaId);
                }
            });
        } catch (UniqueConstraintViolationException $e) {
            // Carrera de dos altas simultaneas: la BD gano. Todo hizo
            // rollback, no quedo ningun registro a medias.
            $this->addError($campoId, sprintf(
                'No se pudo completar el alta: el %s "%s" o el correo acaban de ser registrados por otro proceso. '
                .'No se guardó ningún dato.',
                $etiqueta,
                $identificador
            ));

            return null;
        } catch (\Throwable $e) {
            report($e);

            $this->addError('email', 'No se pudo completar el alta de la cuenta y la persona. No se guardó ningún dato.');

            return null;
        }

        $nombrePersona = $persona !== null ? $this->nombreDeFila($persona) : $this->nombreCompuesto();

        return $persona !== null
            ? sprintf('Cuenta creada y vinculada a %s %s correctamente.', $singular, $nombrePersona)
            : sprintf('Cuenta y persona creadas y vinculadas correctamente (%s %s).', $singular, $nombrePersona);
    }

    /**
     * Busca el correo en las OTRAS entidades de persona.
     * Devuelve los datos necesarios para armar el mensaje o null.
     */
    private function personaEnOtraEntidad(array $entidad, string $email): ?array
    {
        foreach (self::ENTIDADES as $nombreRol => $otra) {
            if ($otra['tabla'] === $entidad['tabla']) {
                continue;
            }

            $fila = DB::table($otra['tabla'])->where('email', $email)->first();

            if ($fila !== null) {
                return [
                    'modulo' => $otra['modulo'],
                    'singular' => $otra['singular'],
                    'etiqueta' => $otra['etiqueta'],
                    'identificador' => $otra['identificador'],
                    'fila' => $fila,
                    'rol' => $nombreRol,
                ];
            }
        }

        return null;
    }

    /* ================================================================
       CARRERAS DEL DOCENTE (N:N con la tabla intermedia carrera_docente)
    ================================================================ */

    /**
     * IDs de carreras capturadas, sin duplicados, validos y ordenados.
     * Se normalizan como enteros para poder compararlos con lo que ya
     * esta guardado en la tabla intermedia.
     */
    private function carrerasDocenteSeleccionadas(): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $this->carreras_ids))));

        sort($ids);

        return $ids;
    }

    /**
     * Carreras que YA tiene asignadas el docente (ids enteros ordenados).
     */
    private function carrerasActualesDelDocente(int $docenteId, bool $forUpdate = false): array
    {
        $consulta = DB::table('carrera_docente')
            ->where('id_docente', $docenteId)
            ->orderBy('id_carrera');

        if ($forUpdate) {
            $consulta->lockForUpdate();
        }

        return $consulta->pluck('id_carrera')
            ->map(fn ($v) => (int) $v)
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Asignaciones actuales de un docente, listas para mostrarse.
     * null cuando el docente no tiene ninguna (no hay nada que confirmar).
     */
    private function detectarDocentePreexistente(): ?array
    {
        if ($this->nombreRolElegido() !== 'Docente') {
            return null;
        }

        $identificador = trim((string) $this->no_empleado);

        if ($identificador === '') {
            return null;
        }

        $persona = DB::table('docentes')->where('no_empleado', $identificador)->first();

        if ($persona === null) {
            return null;
        }

        $carreras = DB::table('carrera_docente')
            ->join('carreras', 'carreras.id_carrera', '=', 'carrera_docente.id_carrera')
            ->where('carrera_docente.id_docente', $persona->id_docente)
            ->orderBy('carreras.nombre')
            ->get(['carreras.id_carrera', 'carreras.clave', 'carreras.nombre'])
            // stdClass -> array plano: la vista accede con $carr['clave'].
            ->map(fn ($fila) => (array) $fila)
            ->toArray();

        if (count($carreras) === 0) {
            return null;
        }

        return [
            'id' => (int) $persona->id_docente,
            'nombre' => $this->nombreDeFila($persona),
            'carreras' => $carreras,
        ];
    }

    /**
     * Proteccion contra la sobrescritura silenciosa de las carreras de un
     * docente existente.
     *
     * Devuelve true si NO hay nada que confirmar (persona nueva, sin
     * carreras previas, o seleccion identica). Cuando las carreras
     * cambiarian, exige la confirmacion explicita del administrador y
     * devuelve false si no esta marcada: en ese caso se agrega el error,
     * se publican las asignaciones actuales en la vista y NO se escribe
     * ningun dato (la transaccion todavia no arranco).
     */
    private function carrerasDeDocenteConfirmadas(object $persona): bool
    {
        $existentes = $this->carrerasActualesDelDocente((int) $persona->id_docente);

        if (count($existentes) === 0) {
            // Sin asignaciones previas: no se sobrescribe nada.
            return true;
        }

        $seleccionadas = $this->carrerasDocenteSeleccionadas();

        if ($existentes === $seleccionadas) {
            // Mismas carreras: no hay cambios que confirmar.
            return true;
        }

        if ($this->confirmarCarreras) {
            return true;
        }

        // Publica en la vista las asignaciones que se reemplazarian.
        $this->docentePreexistente = $this->detectarDocentePreexistente();

        $texto = collect($this->docentePreexistente['carreras'] ?? [])
            ->pluck('clave')
            ->implode(', ');

        $this->addError('carreras_ids', sprintf(
            'El docente %s (%s "%s") ya tiene %d carrera(s) asignada(s): %s, '
            .'pero seleccionaste %d distinta(s). '
            .'Para no sobrescribirlas sin querer, NO se creó la cuenta: '
            .'revisa las asignaciones actuales y marca la casilla de confirmación '
            .'solo si deseas reemplazarlas.',
            $this->nombreDeFila($persona),
            'número de empleado',
            trim((string) $this->no_empleado),
            count($existentes),
            $texto !== '' ? $texto : '—',
            count($seleccionadas)
        ));

        return false;
    }

    /**
     * Reemplaza (atomicamente) las carreras de un docente por las
     * seleccionadas. Se invoca SIEMPRE dentro de la transaccion del alta.
     *
     * Si la lista llegara vacia se lanza una excepcion para que todo se
     * revierta: jamas se borran carreras sin haber validado una
     * seleccion nueva.
     */
    private function guardarCarrerasDelDocente(int $docenteId): void
    {
        $seleccionadas = $this->carrerasDocenteSeleccionadas();

        if (count($seleccionadas) === 0) {
            throw new \RuntimeException('Alta de docente sin carreras seleccionadas: se abortó el proceso.');
        }

        // Reemplazo explicito: primero se limpian las filas del docente y
        // despues se insertan las nuevas. Las claves compuestas impiden
        // cualquier duplicado aunque la lista lo contenga.
        DB::table('carrera_docente')->where('id_docente', $docenteId)->delete();

        foreach ($seleccionadas as $idCarrera) {
            DB::table('carrera_docente')->insert([
                'id_carrera' => $idCarrera,
                'id_docente' => $docenteId,
            ]);
        }
    }

    /**
     * Fila a persistir en la tabla de personas (alumnos / docentes /
     * jefes_carrera), respetando el esquema real de cada una.
     */
    private function datosNuevaPersona(array $entidad): array
    {
        $ahora = now();

        $datos = [
            'nombre' => trim($this->p_nombre),
            'apellido_paterno' => trim($this->p_apellido_paterno),
            // La columna es nullable: se guarda NULL en lugar de cadena vacia.
            'apellido_materno' => trim((string) $this->p_apellido_materno) ?: null,
            'email' => trim($this->email),
            'activo' => 1,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ];

        $datos[$entidad['identificador']] = trim((string) $this->{$entidad['identificador']});

        if ($entidad['con_carrera']) {
            $datos['id_carrera'] = (int) $this->id_carrera;
        }

        if ($entidad['con_fecha_inicio']) {
            $datos['fecha_inicio'] = $this->fecha_inicio;
        }

        return $datos;
    }

    /**
     * users.name a partir de los nombres y apellidos capturados.
     * NO se divide ni se deriva de users.name: se capturan por separado.
     */
    private function nombreCompuesto(): string
    {
        return trim($this->p_nombre.' '.$this->p_apellido_paterno.' '.trim((string) $this->p_apellido_materno));
    }

    /** Nombre completo de una fila de personas, para los mensajes. */
    private function nombreDeFila(object $fila): string
    {
        return trim($fila->nombre.' '.$fila->apellido_paterno.' '.($fila->apellido_materno ?? ''));
    }

    /* ================================================================
       RESTO DEL MODULO (sin cambios funcionales)
    ================================================================ */

    /**
     * Restablecer contrasena desde Administracion: accion/modal separada.
     * Nunca se muestra ni se almacena texto plano (cast 'hashed' del modelo).
     */
    public function abrirModalPassword($userId): void
    {
        $this->exigirPermiso('editar');

        $this->showModal = false;
        $this->showPasswordModal = false;
        $this->passwordTargetId = (int) $userId;
        $this->nuevaPassword = '';
        $this->confirmarPassword = '';
        $this->resetValidation();
        $this->showPasswordModal = true;
    }

    public function guardarPassword(): void
    {
        $this->exigirPermiso('editar');

        $this->validate([
            'nuevaPassword' => ['required', 'string', Password::defaults()],
            'confirmarPassword' => ['required', 'same:nuevaPassword'],
        ], $this->mensajesValidacion());

        $usuario = User::findOrFail($this->passwordTargetId);

        // Cast 'hashed': guarda unicamente el hash (bcrypt), jamas texto plano.
        $usuario->password = $this->nuevaPassword;
        $usuario->save();

        $this->showPasswordModal = false;
        $this->passwordTargetId = null;
        $this->nuevaPassword = '';
        $this->confirmarPassword = '';
        $this->resetValidation();

        session()->flash('success', 'Contraseña restablecida correctamente.');
    }

    /**
     * Activar / desactivar cuentas (sin borrado fisico).
     *
     * - Desactivar = accion "eliminar" del modulo "Usuarios y Roles".
     * - Reactivar  = accion "editar".
     * - La cuenta autenticada no puede desactivarse a si misma (tambien
     *   esta deshabilitada en la interfaz).
     */
    public function toggleActivo($userId): void
    {
        $usuario = User::findOrFail($userId);

        $this->exigirPermiso($usuario->activo ? 'eliminar' : 'editar');

        if ($usuario->id === (int) Auth::id()) {
            session()->flash('error', 'No puedes desactivar tu propia cuenta.');

            return;
        }

        $usuario->activo = ! $usuario->activo;
        $usuario->save();

        $this->cargarUsuarios();
        $this->dispatch('actualizar-personal-control-escolar');

        session()->flash(
            'success',
            $usuario->activo
                ? 'Cuenta reactivada correctamente.'
                : 'Cuenta desactivada. El usuario ya no podrá iniciar sesión.'
        );
    }

    private function resetForm(): void
    {
        $this->resetValidation();
        $this->editingUserId = null;
        $this->nombre = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->rolId = '';
        $this->rolIdOriginal = '';
        $this->rolIdDatosPersona = '';
        $this->activo = true;
        $this->esCuentaAdministrador = false;

        // Datos de persona compartidos por alta y edición.
        $this->p_nombre = '';
        $this->p_apellido_paterno = '';
        $this->p_apellido_materno = '';
        $this->no_control = '';
        $this->no_empleado = '';
        $this->id_carrera = '';
        $this->fecha_inicio = '';

        // Carreras del docente (N:N), selecciones previas y confirmación.
        $this->carreras_ids = [];
        $this->carrerasEdicion = [];
        $this->carrerasOriginales = [];
        $this->datosPersonaPorRol = [];
        $this->datosPersonaBase = [];
        $this->identificadorPersonaDetectado = '';
        $this->carrerasPorAgregar = [];
        $this->carrerasPorRetirar = [];
        $this->carrerasBaseConfirmacion = [];
        $this->rolesEdicion = $this->roles;
        $this->confirmarCarreras = false;
        $this->docentePreexistente = null;

        // Cambios sin guardar (solo EDICION): se reinician con el form.
        $this->formOriginal = [];
        $this->confirmarCierrePendiente = false;
        $this->mostrarConfirmacionGuardado = false;
    }

    /** Nombre del rol seleccionado en el formulario activo ('' si no hay). */
    public function nombreRolElegido(): string
    {
        $catalogo = $this->editingUserId !== null ? $this->rolesEdicion : $this->roles;

        foreach ($catalogo as $rol) {
            if ((int) $rol['id_rol'] === (int) $this->rolId) {
                return (string) $rol['nombre'];
            }
        }

        return '';
    }

    /**
     * Mensajes de validacion en espanol (el proyecto no incluye lang/).
     */
    private function mensajesValidacion(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo electrónico no tiene un formato válido.',
            'email.max' => 'El correo electrónico no puede superar los 100 caracteres.',
            'email.unique' => 'Ese correo ya está registrado en otra cuenta.',
            'rolId.required' => 'Debes seleccionar un rol.',
            'rolId.exists' => 'El rol seleccionado no existe.',
            'activo.required' => 'Debes indicar el estado de la cuenta.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.password' => 'La contraseña debe tener al menos 8 caracteres.',
            'nuevaPassword.required' => 'La nueva contraseña es obligatoria.',
            'nuevaPassword.password' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'confirmarPassword.required' => 'Debes confirmar la nueva contraseña.',
            'confirmarPassword.same' => 'La confirmación no coincide con la nueva contraseña.',

            // ---- Datos de persona (alta y edición) ----
            'p_nombre.required' => 'El nombre de la persona es obligatorio.',
            'p_nombre.min' => 'El nombre debe tener al menos 2 caracteres.',
            'p_nombre.max' => 'El nombre no puede superar los 50 caracteres.',
            'p_apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'p_apellido_paterno.min' => 'El apellido paterno debe tener al menos 2 caracteres.',
            'p_apellido_paterno.max' => 'El apellido paterno no puede superar los 50 caracteres.',
            'p_apellido_materno.max' => 'El apellido materno no puede superar los 50 caracteres.',
            'no_control.required' => 'El número de control es obligatorio.',
            'no_control.min' => 'El número de control debe tener al menos 3 caracteres.',
            'no_control.max' => 'El número de control no puede superar los 20 caracteres.',
            'no_control.unique' => 'Ese número de control ya está registrado para otra persona.',
            'no_empleado.required' => 'El número de empleado es obligatorio.',
            'no_empleado.min' => 'El número de empleado no puede estar vacío.',
            'no_empleado.max' => 'El número de empleado no puede superar los 20 caracteres.',
            'no_empleado.unique' => 'Ese número de empleado ya está registrado para otra persona.',
            'id_carrera.required' => 'Debes seleccionar una carrera.',
            'id_carrera.exists' => 'La carrera seleccionada no existe.',
            'carreras_ids.required' => 'Debes seleccionar al menos una carrera.',
            'carreras_ids.array' => 'La selección de carreras no es válida.',
            'carreras_ids.min' => 'Debes seleccionar al menos una carrera.',
            'carreras_ids.*.exists' => 'Una de las carreras seleccionadas ya no existe.',
            'carreras_ids.*.integer' => 'La selección de carreras no es válida.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio no tiene un formato válido.',
        ];
    }

    /**
     * Infraestructura de permisos de la Etapa 2 (Gate "{Módulo}.{acción}").
     */
    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::check('Usuarios y Roles.'.$accion), 403);
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        // Datos derivados del rol seleccionado: se usan en alta y edición.
        $rolElegido = $this->esCuentaAdministrador
            ? 'Administrador'
            : $this->nombreRolElegido();
        $mapaCarrerasEdicion = collect($this->carrerasEdicion)->keyBy('id_carrera');
        $carrerasAgregadasConfirmacion = collect($this->carrerasPorAgregar)
            ->map(fn ($id) => $mapaCarrerasEdicion->get($id)['clave'] ?? (string) $id)
            ->values()
            ->all();
        $carrerasRetiradasConfirmacion = collect($this->carrerasPorRetirar)
            ->map(fn ($id) => $mapaCarrerasEdicion->get($id)['clave'] ?? (string) $id)
            ->values()
            ->all();

        return view('admin.usuarios.index', [
            'rolElegido' => $rolElegido,
            'rolOriginalNombre' => Role::find((int) $this->rolIdOriginal)?->nombre ?? '',
            'esCreacion' => $this->editingUserId === null,
            'rolConPersona' => in_array($rolElegido, self::ROLES_CON_PERSONA, true),
            'rolConCarrera' => in_array($rolElegido, self::ROLES_CON_CARRERA, true),
            'rolConMultiCarrera' => in_array($rolElegido, self::ROLES_CON_MULTI_CARRERA, true),
            'rolConFechaInicio' => in_array($rolElegido, self::ROLES_CON_FECHA_INICIO, true),
            'carrerasAgregadasConfirmacion' => $carrerasAgregadasConfirmacion,
            'carrerasRetiradasConfirmacion' => $carrerasRetiradasConfirmacion,
        ]);
    }
}
