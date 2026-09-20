<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Role;
use App\Models\Permiso;

class MatrizPermisos extends Component
{
    // Estructura: $matriz[id_rol][id_permiso] = true/false
    public array $matriz = [];
    public bool $guardado = false;

    public function mount()
    {
        $this->cargarMatriz();
    }

    public function cargarMatriz()
    {
        // Traemos todos los roles activos con sus permisos asociados
        $roles = Role::with('permisos')->where('activo', true)->get();
        $permisos = Permiso::all();

        foreach ($roles as $role) {
            // Obtenemos las claves primarias (id_permiso) asociadas al rol
            $idsAsignados = $role->permisos->pluck('id_permiso')->toArray();

            foreach ($permisos as $permiso) {
                // Mapeamos a boolean según la existencia en la tabla pivote
                $this->matriz[$role->id_rol][$permiso->id_permiso] = in_array($permiso->id_permiso, $idsAsignados);
            }
        }
    }

    public function guardar()
    {
        foreach ($this->matriz as $idRol => $permisosDict) {
            $role = Role::find($idRol);

            if ($role) {
                // Filtramos únicamente los permisos marcados (true) y extraemos sus IDs
                $permisosSeleccionados = array_keys(array_filter($permisosDict));

                // Sincronización automática en la tabla pivote 'rol_permiso'
                $role->permisos()->sync($permisosSeleccionados);
            }
        }

        $this->guardado = true;
    }

    public function render()
    {
        return view('livewire.admin.matriz-permisos', [
            'roles' => Role::where('activo', true)->get(),
            'modulos' => Permiso::all()->groupBy('modulo'),
        ])->layout('layouts.app');
    }
}