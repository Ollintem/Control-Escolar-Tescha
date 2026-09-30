<?php

namespace App\Livewire;

use App\Models\Alumno;
use App\Models\Docente;
use App\Models\JefeCarrera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Gestion de CUENTAS DE USUARIO del Panel del Administrador.
 *
 * Una cuenta NO es una persona academica: este modulo nunca crea docentes,
 * jefes de carrera ni alumnos. Solo vincula la cuenta a una persona que ya
 * exista y que aun no este vinculada a otra cuenta (los vinculos son UNIQUE).
 *
 * Fuente oficial del rol: users.FK_id_rol -> roles.id_rol (tabla roles).
 * La columna ENUM legacy users.rol NO se escribe ni se usa en ninguna logica.
 *
 * Permisos (Etapa 2, modulo "Usuarios y Roles"):
 *   ver     -> consultar el listado
 *   crear   -> crear cuentas y reactivar cuentas
 *   editar  -> editar cuentas y restablecer contrasenas
 *   eliminar-> desactivar cuentas (no existe borrado fisico; ver informe)
 */
class GestionUsuarios extends Component
{
    // Listado y busqueda
    public $usuarios = [];
    public $search = '';
    public $userCount = 0;

    // Catalogo de roles (fuente oficial: tabla roles)
    public $roles = [];

    // Modal Crear / Editar
    public $showModal = false;
    public $editingUserId = null;
    public $nombre = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $rolId = '';
    public $activo = true;
    public $idDocente = '';
    public $idJefeCarrera = '';
    public $idAlumno = '';

    // Personas disponibles (solo las NO vinculadas a otra cuenta)
    public $docentesDisponibles = [];
    public $jefesDisponibles = [];
    public $alumnosDisponibles = [];

    // Modal de restablecer contrasena
    public $showPasswordModal = false;
    public $passwordTargetId = null;
    public $nuevaPassword = '';
    public $confirmarPassword = '';

    public function mount(): void
    {
        $this->roles = Role::where('activo', 1)
            ->orderBy('id_rol')
            ->get(['id_rol', 'nombre'])
            ->toArray();

        $this->cargarUsuarios();
        $this->cargarPersonasDisponibles();
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
     * Texto descriptivo de la persona vinculada para el listado.
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

    /**
     * Carga las personas disponibles para los selectores, excluyendo las que
     * ya estan vinculadas a OTRA cuenta (los vinculos son UNIQUE). Al editar,
     * el vinculo actual de la cuenta en edicion se conserva en la lista.
     */
    public function cargarPersonasDisponibles(): void
    {
        $vinculados = fn (string $columna) => User::query()
            ->whereNotNull($columna)
            ->when($this->editingUserId, fn ($q) => $q->where('id', '<>', $this->editingUserId))
            ->pluck($columna);

        $this->docentesDisponibles = Docente::orderBy('nombre')
            ->whereNotIn('id_docente', $vinculados('id_docente'))
            ->get()
            ->map(fn (Docente $d) => [
                'id' => $d->id_docente,
                'etiqueta' => trim($d->nombre . ' ' . $d->apellido_paterno . ' ' . $d->apellido_materno)
                    . ' · Núm. empleado ' . $d->no_empleado
                    . ' · ' . $d->email,
            ])
            ->toArray();

        $this->jefesDisponibles = JefeCarrera::orderBy('nombre')
            ->whereNotIn('id_jefe_carrera', $vinculados('id_jefe_carrera'))
            ->get()
            ->map(fn (JefeCarrera $j) => [
                'id' => $j->id_jefe_carrera,
                'etiqueta' => trim($j->nombre . ' ' . $j->apellido_paterno . ' ' . $j->apellido_materno)
                    . ' · Núm. empleado ' . $j->no_empleado
                    . ' · ' . $j->email,
            ])
            ->toArray();

        $this->alumnosDisponibles = Alumno::orderBy('nombre')
            ->whereNotIn('id_alumno', $vinculados('id_alumno'))
            ->get()
            ->map(fn (Alumno $a) => [
                'id' => $a->id_alumno,
                'etiqueta' => trim($a->nombre . ' ' . $a->apellido_paterno . ' ' . $a->apellido_materno)
                    . ' · Núm. control ' . $a->no_control
                    . ' · ' . $a->email,
            ])
            ->toArray();
    }

    /**
     * Nombre del rol actualmente seleccionado en el formulario, resuelto
     * siempre contra la tabla roles (nunca contra el ENUM legacy).
     */
    public function nombreRolSeleccionado(): ?string
    {
        if ($this->rolId === '' || $this->rolId === null) {
            return null;
        }

        $rol = collect($this->roles)->firstWhere('id_rol', $this->rolId);

        if ($rol !== null) {
            return $rol['nombre'];
        }

        // Rol existente pero inactivo (no aparece en el select).
        return Role::find($this->rolId)?->nombre;
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');

        $this->resetForm();
        $this->cargarPersonasDisponibles();
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
        $this->idDocente = $usuario->id_docente !== null ? (string) $usuario->id_docente : '';
        $this->idJefeCarrera = $usuario->id_jefe_carrera !== null ? (string) $usuario->id_jefe_carrera : '';
        $this->idAlumno = $usuario->id_alumno !== null ? (string) $usuario->id_alumno : '';

        $this->cargarPersonasDisponibles();
        $this->showPasswordModal = false;
        $this->showModal = true;
    }

    public function guardar(): void
    {
        $this->exigirPermiso($this->editingUserId ? 'editar' : 'crear');

        $reglas = [
            'nombre' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'rolId' => ['required', 'integer', Rule::exists('roles', 'id_rol')],
            'activo' => ['required', 'boolean'],
        ];

        // La contrasena solo se define al CREAR la cuenta; en edicion se usa
        // la accion separada de restablecer contrasena.
        if (! $this->editingUserId) {
            $reglas['password'] = ['required', 'string', Password::defaults(), 'confirmed'];
        }

        $this->validate($reglas, $this->mensajesValidacion());

        // Proteccion de la cuenta autenticada: no puede cambiar su propio rol
        // (evita quedarse sin rol o desvincular su rol de Administrador).
        if ($this->editingUserId !== null) {
            $usuario = User::findOrFail($this->editingUserId);

            if ($usuario->id === (int) Auth::id() && (int) $this->rolId !== (int) $usuario->FK_id_rol) {
                $this->addError('rolId', 'No puedes cambiar el rol de la cuenta con la que has iniciado sesión.');

                return;
            }
        }

        if (! $this->validarVinculos()) {
            return;
        }

        $datos = [
            'name' => $this->nombre,
            'email' => $this->email,
            'activo' => (bool) $this->activo,
            'FK_id_rol' => (int) $this->rolId,
            'id_docente' => $this->idDocente === '' ? null : (int) $this->idDocente,
            'id_jefe_carrera' => $this->idJefeCarrera === '' ? null : (int) $this->idJefeCarrera,
            'id_alumno' => $this->idAlumno === '' ? null : (int) $this->idAlumno,
            // NOTA: la columna ENUM legacy users.rol NO se escribe aqui.
            // Aplica su DEFAULT del esquema y no se usa para autorizacion
            // (fuente oficial: FK_id_rol -> roles.id_rol).
        ];

        $isEditing = $this->editingUserId !== null;

        if ($isEditing) {
            // La contrasena NUNCA se toca desde este formulario.
            $usuario->fill($datos)->save();
        } else {
            // El cast 'hashed' del modelo convierte el texto plano a hash (bcrypt).
            $datos['password'] = $this->password;
            User::create($datos);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->cargarUsuarios();
        $this->cargarPersonasDisponibles();

        session()->flash('success', $isEditing ? 'Usuario actualizado correctamente.' : 'Usuario creado exitosamente.');
    }

    /**
     * Reglas de coherencia rol <-> persona:
     *
     * - Docente            -> exige id_docente; limpia id_jefe_carrera e id_alumno.
     * - Jefe de Carrera    -> exige id_jefe_carrera; limpia id_docente e id_alumno.
     * - Alumno             -> exige id_alumno; limpia id_docente e id_jefe_carrera.
     * - Administrador /
     *   Control Escolar    -> fuerza los tres vinculos a NULL (sin persona).
     *
     * Cada vinculo ademas debe: existir en su tabla y NO estar ya vinculado
     * a otra cuenta (una persona = una cuenta; la BD tambien lo garantiza
     * con claves UNIQUE).
     */
    private function validarVinculos(): bool
    {
        $nombreRol = $this->nombreRolSeleccionado();

        if (! in_array($nombreRol, ['Docente', 'Jefe de Carrera', 'Alumno'], true)) {
            // Administrador y Control Escolar: normalizacion obligatoria a NULL.
            $this->idDocente = '';
            $this->idJefeCarrera = '';
            $this->idAlumno = '';

            return true;
        }

        if ($nombreRol === 'Docente') {
            $this->idJefeCarrera = '';
            $this->idAlumno = '';

            return $this->validarPersona(
                'idDocente',
                Docente::class,
                'id_docente',
                'Debes seleccionar un docente para el rol Docente.'
            );
        }

        if ($nombreRol === 'Jefe de Carrera') {
            $this->idDocente = '';
            $this->idAlumno = '';

            return $this->validarPersona(
                'idJefeCarrera',
                JefeCarrera::class,
                'id_jefe_carrera',
                'Debes seleccionar un jefe de carrera para el rol Jefe de Carrera.'
            );
        }

        // Alumno
        $this->idDocente = '';
        $this->idJefeCarrera = '';

        return $this->validarPersona(
            'idAlumno',
            Alumno::class,
            'id_alumno',
            'Debes seleccionar un alumno para el rol Alumno.'
        );
    }

    private function validarPersona(string $propiedad, string $modelo, string $columna, string $mensajeVacio): bool
    {
        $valor = $this->{$propiedad};

        if ($valor === '' || $valor === null) {
            $this->addError($propiedad, $mensajeVacio);

            return false;
        }

        if (! $modelo::whereKey($valor)->exists()) {
            $this->addError($propiedad, 'La persona seleccionada no existe.');

            return false;
        }

        $enOtraCuenta = User::query()
            ->where($columna, $valor)
            ->when($this->editingUserId, fn ($q) => $q->where('id', '<>', $this->editingUserId))
            ->exists();

        if ($enOtraCuenta) {
            $this->addError($propiedad, 'Esa persona ya está vinculada a otra cuenta de usuario.');

            return false;
        }

        return true;
    }

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
        $this->idDocente = '';
        $this->idJefeCarrera = '';
        $this->idAlumno = '';
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

        return view('admin.usuarios.index', [
            'nombreRolSeleccionado' => $this->nombreRolSeleccionado(),
        ]);
    }
}
