<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Role;

class CreateRole extends Component
{
    public $nombre_rol = '';
    public $descripcion = '';

    protected function rules(): array
    {
        return [
            'nombre_rol'  => 'required|min:2|max:30|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:120',
        ];
    }

    public function guardar()
    {
        $this->validate();

        Role::create([
            'nombre'      => $this->nombre_rol,
            'descripcion' => $this->descripcion,
            'activo'      => 1,
        ]);

        session()->flash('message', '¡Rol guardado exitosamente!');
        $this->reset(['nombre_rol', 'descripcion']);
    }

    public function render()
    {
        return view('livewire.create-role');
    }
}