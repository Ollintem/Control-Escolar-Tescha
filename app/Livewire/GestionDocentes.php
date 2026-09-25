<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Docente;

class GestionDocentes extends Component
{
    public $docentes = [];
    public $search = '';
    public $nombre = '';
    public $apellido_paterno = '';
    public $apellido_materno = '';
    public $no_empleado = '';
    public $email = '';
    public $editingDocenteId = null;
    public $showModal = false;
    public $docenteCount = 0;

    public function mount()
    {
        $this->cargarDocentes();
    }

    public function cargarDocentes()
    {
        $query = Docente::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('nombre', 'like', "%{$this->search}%")
                  ->orWhere('apellido_paterno', 'like', "%{$this->search}%")
                  ->orWhere('apellido_materno', 'like', "%{$this->search}%")
                  ->orWhere('no_empleado', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        $this->docentes = $query->get()->toArray();
        $this->docenteCount = count($this->docentes);
    }

    public function updatedSearch()
    {
        $this->cargarDocentes();
    }

    public function abrirModalCrear()
    {
        $this->editingDocenteId = null;
        $this->reset(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email']);
        $this->showModal = true;
    }

    public function abrirModalEditar($docenteId)
    {
        $docente = Docente::findOrFail($docenteId);

        $this->editingDocenteId = $docenteId;
        $this->nombre = $docente->nombre;
        $this->apellido_paterno = $docente->apellido_paterno;
        $this->apellido_materno = $docente->apellido_materno;
        $this->no_empleado = $docente->no_empleado;
        $this->email = $docente->email;
        $this->showModal = true;
    }

    public function guardar()
    {
        $this->validate([
            'nombre'           => 'required|min:2|max:50',
            'apellido_paterno' => 'required|min:2|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'no_empleado'      => 'required|min:1|max:20' . ($this->editingDocenteId ? '|unique:docentes,no_empleado,' . $this->editingDocenteId . ',id_docente' : '|unique:docentes,no_empleado'),
            'email'            => 'required|email|max:100' . ($this->editingDocenteId ? '|unique:docentes,email,' . $this->editingDocenteId . ',id_docente' : '|unique:docentes,email'),
        ]);

        if ($this->editingDocenteId) {
            $docente = Docente::findOrFail($this->editingDocenteId);
            $docente->update([
                'nombre'           => $this->nombre,
                'apellido_paterno' => $this->apellido_paterno,
                'apellido_materno' => $this->apellido_materno,
                'no_empleado'      => $this->no_empleado,
                'email'            => $this->email,
            ]);
        } else {
            Docente::create([
                'nombre'           => $this->nombre,
                'apellido_paterno' => $this->apellido_paterno,
                'apellido_materno' => $this->apellido_materno,
                'no_empleado'      => $this->no_empleado,
                'email'            => $this->email,
                'activo'           => 1,
            ]);
        }

        $isEditing = $this->editingDocenteId;

        $this->showModal = false;
        $this->reset(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email']);
        $this->editingDocenteId = null;
        $this->cargarDocentes();

        session()->flash('success', $isEditing ? 'Docente actualizado correctamente.' : 'Docente registrado exitosamente.');
    }

    public function eliminar($docenteId)
    {
        $docente = Docente::findOrFail($docenteId);
        $docente->delete();
        $this->cargarDocentes();

        session()->flash('success', 'Docente eliminado correctamente.');
    }

    public function toggleActivo($docenteId)
    {
        $docente = Docente::findOrFail($docenteId);
        $docente->activo = !$docente->activo;
        $docente->save();
        $this->cargarDocentes();
    }

    public function render()
    {
        return view('livewire.gestion-docentes');
    }
}
