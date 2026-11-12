<?php

namespace App\Livewire;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class GestionRoles extends Component
{
    public $roles = [];

    public $search = '';

    public $nombre = '';

    public $descripcion = '';

    public $editingRoleId = null;

    public $showModal = false;

    public $roleCount = 0;

    public function mount(): void
    {
        $this->cargarRoles();
    }

    public function cargarRoles(): void
    {
        $this->exigirPermiso('ver');

        $query = Role::withCount('permisos');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                    ->orWhere('descripcion', 'like', "%{$this->search}%");
            });
        }

        $this->roles = $query->get()->toArray();
        $this->roleCount = count($this->roles);
    }

    public function updatedSearch(): void
    {
        $this->cargarRoles();
    }

    public function abrirModalCrear(): void
    {
        $this->exigirPermiso('crear');

        $this->editingRoleId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->showModal = true;
    }

    public function abrirModalEditar(int $roleId): void
    {
        $this->exigirPermiso('editar');

        $role = Role::findOrFail($roleId);

        $this->editingRoleId = $roleId;
        $this->nombre = $role->nombre;
        $this->descripcion = $role->descripcion;
        $this->showModal = true;
    }

    public function guardar(): void
    {
        $isEditing = $this->editingRoleId !== null && $this->editingRoleId !== '';
        $this->exigirPermiso($isEditing ? 'editar' : 'crear');

        $role = $isEditing ? Role::findOrFail($this->editingRoleId) : null;
        $isAdministrator = $role !== null && $this->esRolAdministrador($role);

        if ($isAdministrator) {
            $this->validate([
                'descripcion' => 'nullable|string|max:120',
            ]);

            $role->update(['descripcion' => $this->descripcion]);
        } else {
            $this->validate([
                'nombre' => 'required|min:2|max:30'.($isEditing ? '|unique:roles,nombre,'.$role->id_rol.',id_rol' : '|unique:roles,nombre'),
                'descripcion' => 'nullable|string|max:120',
            ]);

            if ($this->normalizarNombre($this->nombre) === 'administrador') {
                $this->addError('nombre', 'El nombre Administrador está reservado y no puede asignarse a otro rol.');

                return;
            }

            if ($isEditing) {
                $role->update([
                    'nombre' => $this->nombre,
                    'descripcion' => $this->descripcion,
                ]);
            } else {
                Role::create([
                    'nombre' => $this->nombre,
                    'descripcion' => $this->descripcion,
                    'activo' => 1,
                ]);
            }
        }

        $this->showModal = false;
        $this->nombre = '';
        $this->descripcion = '';
        $this->editingRoleId = null;
        $this->cargarRoles();

        session()->flash('success', $isEditing ? 'Rol actualizado correctamente.' : 'Rol creado exitosamente.');
    }

    public function eliminar(int $roleId): void
    {
        $this->exigirPermiso('eliminar');

        try {
            $resultado = DB::transaction(function () use ($roleId): string {
                $role = Role::query()->lockForUpdate()->findOrFail($roleId);

                if ($this->esRolAdministrador($role)) {
                    return 'administrador';
                }

                if (User::query()->where('FK_id_rol', $role->id_rol)->exists()) {
                    return 'usuarios';
                }

                return $role->delete() ? 'eliminado' : 'fallo';
            });
        } catch (ModelNotFoundException) {
            session()->flash('error', 'No se encontró el rol que se intentó eliminar.');

            return;
        } catch (QueryException) {
            session()->flash('error', 'No se pudo eliminar el rol porque existen cuentas relacionadas o una restricción de integridad lo impide.');

            return;
        }

        if ($resultado === 'administrador') {
            session()->flash('error', 'El rol Administrador no se puede eliminar.');

            return;
        }

        if ($resultado === 'usuarios') {
            session()->flash('error', 'No se puede eliminar este rol porque tiene cuentas de usuario asignadas.');

            return;
        }

        if ($resultado !== 'eliminado') {
            session()->flash('error', 'No se pudo eliminar el rol. El registro se conservó.');

            return;
        }

        $this->cargarRoles();

        session()->flash('success', 'Rol eliminado correctamente.');
    }

    public function render(): View
    {
        $this->exigirPermiso('ver');

        return view('admin.roles.index');
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::allows('Usuarios y Roles.'.$accion), 403);
    }

    private function esRolAdministrador(Role $role): bool
    {
        return $this->normalizarNombre($role->nombre) === 'administrador';
    }

    private function normalizarNombre(string $nombre): string
    {
        return mb_strtolower(trim($nombre));
    }
}
