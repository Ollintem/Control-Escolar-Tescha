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
 * EDICION: SIN CAMBIOS en esta etapa.
 * =========================================================================
 * En edicion el formulario sigue siendo nombre / correo / rol / estado.
 * Las columnas de persona NO se tocan (se conservan), salvo la
 * normalizacion a NULL que ya existia para roles sin persona. El cambio
 * de rol entre entidades (Alumno -> Docente, etc.) es una etapa futura.
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
    public $activo = true;

    // ---- Datos de persona: SOLO se usan en ALTA y solo cuando el rol
    // ---- seleccionado tiene entidad propia (Alumno / Docente / Jefe).
    // ---- En EDICION nunca se leen ni se escriben (etapa posterior).
    public $p_nombre = '';
    public $p_apellido_paterno = '';
    public $p_apellido_materno = '';
    public $no_control = '';
    public $no_empleado = '';
    public $id_carrera = '';
    public $fecha_inicio = '';

    // true solo al editar la cuenta que YA tiene el rol Administrador
    // (el rol se muestra en solo lectura; no se puede elegir ni cambiar).
    public $esCuentaAdministrador = false;

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
     * Al cambiar de rol mientras se da de alta, se limpian los mensajes de
     * validacion del rol anterior (esos campos ya no se muestran ni se
     * validan). En EDICION no hace nada: el formulario no depende del rol.
     */
    public function updatedRolId(): void
    {
        if ($this->editingUserId === null) {
            $this->resetValidation();
        }
    }

    /**
     * Texto descriptivo de la persona vinculada para el listado (solo
     * lectura informativa).
     */
    private function textoPersona(User $u): ?string
    {
        if ($u->id_docente !== null && $u->docente) {
            $d = $u->docente;

            return 'Docente · ' . trim($d->nombre . ' ' . $d->apellido_paterno . ' ' . $d->apellido_materno)
                . ' · Núm. empleado ' . $d->no_empleado;
        }

        if ($u->id_jefe_carrera !== null && $u->jefeCarrera) {
            $j = $u->jefeCarrera;

            return 'Jefe de Carrera · ' . trim($j->nombre . ' ' . $j->apellido_paterno . ' ' . $j->apellido_materno)
                . ' · Núm. empleado ' . $j->no_empleado;
        }

        if ($u->id_alumno !== null && $u->alumno) {
            $a = $u->alumno;

            return 'Alumno · ' . trim($a->nombre . ' ' . $a->apellido_paterno . ' ' . $a->apellido_materno)
                . ' · Núm. control ' . $a->no_control;
        }

        return null;
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');

        $this->resetForm();
        $this->showPasswordModal = false;
        $this->showModal = true;
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
        $this->activo = (bool) $usuario->activo;
        $this->esCuentaAdministrador = $usuario->role?->nombre === 'Administrador';

        $this->showPasswordModal = false;
        $this->showModal = true;
    }

    public function guardar(): void
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
        $creaPersona = $esCreacion
            && $rolEnFormulario !== null
            && in_array($rolNombre, self::ROLES_CON_PERSONA, true);

        $reglas = [
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'rolId' => ['required', 'integer', Rule::exists('roles', 'id_rol')],
            'activo' => ['required', 'boolean'],
        ];

        // La contrasena solo se define al CREAR la cuenta; en edicion se usa
        // la accion separada de restablecer contrasena.
        if ($esCreacion) {
            $reglas['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }

        if ($creaPersona) {
            $reglas = array_merge($reglas, $this->reglasPersona($rolNombre));
        } else {
            // Sin persona (edicion, o alta de Control Escolar): se captura
            // el nombre completo de la cuenta, igual que hasta ahora.
            $reglas['nombre'] = ['required', 'string', 'min:3', 'max:255'];
        }

        $this->validate($reglas, $this->mensajesValidacion());

        $usuario = $this->editingUserId !== null ? User::findOrFail($this->editingUserId) : null;

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
            // =========================================================
            // EDICION: SIN CAMBIOS respecto de la etapa anterior.
            // =========================================================
            $datos = [
                'name' => $this->nombre,
                'email' => $this->email,
                'activo' => (bool) $this->activo,
                'FK_id_rol' => (int) $this->rolId,
                // NOTA: la columna ENUM legacy users.rol NO se escribe aqui.
                // Aplica su DEFAULT del esquema y no se usa para autorizacion
                // (fuente oficial: FK_id_rol -> roles.id_rol).
            ];

            // Las columnas de persona NO se gestionan en este formulario:
            // se conservan tal cual. Unica excepcion: roles sin persona
            // (Administrador / Control Escolar) normalizan a NULL (Etapa 3).
            if (! in_array($rol->nombre, ['Docente', 'Jefe de Carrera', 'Alumno'], true)) {
                $datos['id_docente'] = null;
                $datos['id_jefe_carrera'] = null;
                $datos['id_alumno'] = null;
            }

            // La contrasena NUNCA se toca desde este formulario.
            $usuario->fill($datos)->save();

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
        $this->resetForm();
        $this->cargarUsuarios();

        session()->flash('success', $mensajeExito);
    }

    /* ================================================================
       ALTA DE CUENTA + PERSONA
    ================================================================ */

    /**
     * Reglas adicionales del formulario cuando el rol elegido representa
     * una persona academica.
     *
     * NOTA: NO se agrega la regla "unique" sobre no_control / no_empleado.
     * La unicidad se resuelve en crearCuentaConPersona(): si la persona ya
     * existe y es consistente hay que VINCULARLA (no rechazarla), y si es
     * conflictiva hay que avisar con un mensaje claro. Una regla "unique"
     * ciega rechazaría el caso valido de vinculación.
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

        if (in_array($rolNombre, self::ROLES_CON_FECHA_INICIO, true)) {
            $reglas['fecha_inicio'] = ['required', 'date'];
        }

        return $reglas;
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
                    . 'Por seguridad NO se creó la cuenta: confirma primero si se trata de la misma persona.',
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
                    . 'No se pudo determinar que sea la misma persona, por lo que NO se creó la cuenta.',
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
                    . 'No es posible confirmar que sea la misma persona, por lo que NO se creó la cuenta.',
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
                    . 'No se creó otra cuenta ni se modificó la existente.',
                    $singular,
                    $this->nombreDeFila($persona),
                    $cuenta->email
                ));

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
            });
        } catch (UniqueConstraintViolationException $e) {
            // Carrera de dos altas simultaneas: la BD gano. Todo hizo
            // rollback, no quedo ningun registro a medias.
            $this->addError($campoId, sprintf(
                'No se pudo completar el alta: el %s "%s" o el correo acaban de ser registrados por otro proceso. '
                . 'No se guardó ningún dato.',
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
        return trim($this->p_nombre . ' ' . $this->p_apellido_paterno . ' ' . trim((string) $this->p_apellido_materno));
    }

    /** Nombre completo de una fila de personas, para los mensajes. */
    private function nombreDeFila(object $fila): string
    {
        return trim($fila->nombre . ' ' . $fila->apellido_paterno . ' ' . ($fila->apellido_materno ?? ''));
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
        $this->activo = true;
        $this->esCuentaAdministrador = false;

        // Datos de persona: solo viven en el ALTA.
        $this->p_nombre = '';
        $this->p_apellido_paterno = '';
        $this->p_apellido_materno = '';
        $this->no_control = '';
        $this->no_empleado = '';
        $this->id_carrera = '';
        $this->fecha_inicio = '';
    }

    /**
     * Nombre del rol elegado en el formulario de ALTA ('' si no hay).
     * Se usa por la vista para mostrar/ocultar los campos de persona.
     */
    public function nombreRolElegido(): string
    {
        foreach ($this->roles as $rol) {
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

            // ---- Datos de persona (solo ALTA) ----
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
            'no_empleado.required' => 'El número de empleado es obligatorio.',
            'no_empleado.min' => 'El número de empleado no puede estar vacío.',
            'no_empleado.max' => 'El número de empleado no puede superar los 20 caracteres.',
            'id_carrera.required' => 'Debes seleccionar una carrera.',
            'id_carrera.exists' => 'La carrera seleccionada no existe.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio.date' => 'La fecha de inicio no tiene un formato válido.',
        ];
    }

    /**
     * Infraestructura de permisos de la Etapa 2 (Gate "{Módulo}.{acción}").
     */
    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::check('Usuarios y Roles.' . $accion), 403);
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        // Datos derivados del rol elegido: definen que campos muestra el
        // formulario de ALTA. Se calculan en cada render, por lo que
        // reaccionan al cambio del selector (wire:model.live).
        $rolElegido = $this->nombreRolElegido();

        return view('admin.usuarios.index', [
            'rolElegido' => $rolElegido,
            'rolConPersona' => in_array($rolElegido, self::ROLES_CON_PERSONA, true),
            'rolConCarrera' => in_array($rolElegido, self::ROLES_CON_CARRERA, true),
            'rolConFechaInicio' => in_array($rolElegido, self::ROLES_CON_FECHA_INICIO, true),
        ]);
    }
}
