<?php

namespace App\Livewire;

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
 * Una cuenta NO es una persona academica: este modulo solo administra
 * nombre, correo, contrasena, rol y estado.
 *
 * - NO crea ni vincula personas: los campos de persona se eliminaron del
 *   formulario. En ALTA las columnas id_docente/id_jefe_carrera/id_alumno
 *   quedan NULL (omision); en EDICION no se tocan (se conserva cualquier
 *   vinculo heredado). La columna "Persona vinculada" del listado es solo
 *   informativa (lectura NULL-safe).
 *
 * - Solo puede existir UNA cuenta con rol Administrador: la opcion no
 *   aparece en el selector y ademas el servidor la rechaza aunque la
 *   pagina se manipule (la cuenta existente puede conservar su rol).
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

    // Catalogo de roles (fuente oficial: tabla roles). NUNCA incluye
    // "Administrador": solo se permite una cuenta con ese rol.
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
     * Texto descriptivo de la persona vinculada para el listado (solo
     * lectura informativa: este modulo no gestiona los vinculos).
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

        $datos = [
            'name' => $this->nombre,
            'email' => $this->email,
            'activo' => (bool) $this->activo,
            'FK_id_rol' => (int) $this->rolId,
            // NOTA: la columna ENUM legacy users.rol NO se escribe aqui.
            // Aplica su DEFAULT del esquema y no se usa para autorizacion
            // (fuente oficial: FK_id_rol -> roles.id_rol).
        ];

        $isEditing = $this->editingUserId !== null;

        if ($isEditing) {
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
        } else {
            // El cast 'hashed' del modelo convierte el texto plano a hash (bcrypt).
            // Las columnas de persona se omiten: quedan NULL (nullable).
            $datos['password'] = $this->password;
            User::create($datos);
        }

        $this->showModal = false;
        $this->resetForm();
        $this->cargarUsuarios();

        session()->flash('success', $isEditing ? 'Usuario actualizado correctamente.' : 'Usuario creado exitosamente.');
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
        $this->esCuentaAdministrador = false;
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

        return view('admin.usuarios.index');
    }
}
