<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class GestionRoles extends Component
{
    public $roles = [];
    public $search = '';
    public $nombre = '';
    public $descripcion = '';
    public $editingRoleId = null;
    public $showModal = false;
    public $roleCount = 0;

    public function mount()
    {
        $this->cargarRoles();
    }

    public function cargarRoles()
    {
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

    public function updatedSearch()
    {
        $this->cargarRoles();
    }

    public function abrirModalCrear()
    {
        $this->editingRoleId = null;
        $this->nombre = '';
        $this->descripcion = '';
        $this->showModal = true;
    }

    public function abrirModalEditar($roleId)
    {
        $role = Role::findOrFail($roleId);

        // No permitir editar el nombre del Administrador
        $this->editingRoleId = $roleId;
        $this->nombre = $role->nombre;
        $this->descripcion = $role->descripcion;
        $this->showModal = true;
    }

    public function guardar()
    {
        // Validar
        $this->validate([
            'nombre' => 'required|min:2|max:30' . ($this->editingRoleId ? '' : '|unique:roles,nombre'),
            'descripcion' => 'nullable|string|max:120',
        ]);

        if ($this->editingRoleId) {
            // Actualizar
            $role = Role::findOrFail($this->editingRoleId);

            // El Administrador solo puede cambiar descripción
            if ($role->id_rol == 1) {
                $role->update(['descripcion' => $this->descripcion]);
            } else {
                $this->validate([
                    'nombre' => 'required|min:2|max:30|unique:roles,nombre,' . $this->editingRoleId . ',id_rol',
                ]);
                $role->update([
                    'nombre' => $this->nombre,
                    'descripcion' => $this->descripcion,
                ]);
            }
        } else {
            // Crear
            Role::create([
                'nombre' => $this->nombre,
                'descripcion' => $this->descripcion,
                'activo' => 1,
            ]);
        }

        $isEditing = $this->editingRoleId;

        $this->showModal = false;
        $this->nombre = '';
        $this->descripcion = '';
        $this->editingRoleId = null;
        $this->cargarRoles();

        session()->flash('success', $isEditing ? 'Rol actualizado correctamente.' : 'Rol creado exitosamente.');
    }

    public function eliminar($roleId)
    {
        $role = Role::findOrFail($roleId);

        // No eliminar el Administrador
        if ($roleId == 1) {
            session()->flash('error', 'El rol Administrador no se puede eliminar.');
            return;
        }

        $role->delete();
        $this->cargarRoles();

        session()->flash('success', 'Rol eliminado correctamente.');
    }

    public function render()
    {
        return view('livewire.gestion-roles');
    }
}
