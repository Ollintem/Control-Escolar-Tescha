<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Role;

class CreateRole extends Component
{
    public $nombre_rol = '';
    public $descripcion = '';

    protected $rules = [
        'nombre_rol' => 'required|min:3|unique:roles,nombre_rol', // Nombre de la columna en la tabla roles
        'descripcion' => 'nullable|string|max:255',
    ];

    public function guardar()
    {
        $this->validate();

        // Guardar en la BD usando tu modelo Role
        Role::create([
            'nombre_rol' => $this->nombre_rol,
            'descripcion' => $this->descripcion,
        ]);

        session()->flash('message', '¡Rol guardado exitosamente!');
        $this->reset(['nombre_rol', 'descripcion']);
    }

    public function render()
    {
        return view('livewire.create-role');
    }
}