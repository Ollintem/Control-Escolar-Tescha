<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

class MatrizPermisosDashboard extends Component
{
    public array $matriz = [];
    public array $roles = [];
    public array $modulos = [];
    public bool $guardado = false;

    public function mount()
    {
        $this->cargarMatriz();
    }

    public function cargarMatriz()
    {
        $rolesCollection = Role::with('permisos')->where('activo', true)->get();
        $permisos = Permiso::all();

        $this->roles = $rolesCollection->map(fn($r) => [
            'id_rol'   => $r->id_rol,
            'nombre'   => $r->nombre,
            'icono'    => $r->id_rol == 1 ? 'shield-check' : 'shield',
            'es_admin' => $r->id_rol == 1,
        ])->toArray();

        $this->modulos = $permisos->groupBy('modulo')->toArray();

        foreach ($rolesCollection as $role) {
            $idsAsignados = $role->permisos->pluck('id_permiso')->toArray();
            foreach ($permisos as $permiso) {
                $this->matriz[$role->id_rol][$permiso->id_permiso] = in_array($permiso->id_permiso, $idsAsignados);
            }
        }
    }

    public function guardar()
    {
        DB::beginTransaction();

        try {
            foreach ($this->matriz as $idRol => $permisosDict) {
                $role = Role::find($idRol);

                if ($role) {
                    $permisosSeleccionados = array_keys(array_filter($permisosDict));
                    $role->permisos()->sync($permisosSeleccionados);
                }
            }

            DB::commit();
            $this->guardado = true;

            session()->flash('success', 'Permisos guardados correctamente en la base de datos.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar permisos: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.matriz-permisos-dashboard');
    }
}
