<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Alumno;
use App\Models\Carrera;

class GestionAlumnos extends Component
{
    public $alumnos = [];
    public $search = '';
    public $id_carrera = '';
    public $no_control = '';
    public $nombre = '';
    public $apellido_paterno = '';
    public $apellido_materno = '';
    public $email = '';
    public $editingAlumnoId = null;
    public $showModal = false;
    public $alumnoCount = 0;

    // Select
    public $carreras = [];

    public function mount()
    {
        $this->cargarAlumnos();
        $this->cargarSelects();
    }

    public function cargarSelects()
    {
        $this->carreras = Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();
    }

    public function cargarAlumnos()
    {
        $query = Alumno::with('carrera');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_control', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido_paterno', 'like', "%{$search}%")
                  ->orWhere('apellido_materno', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('carrera', function ($qc) use ($search) {
                      $qc->where('nombre', 'like', "%{$search}%")
                         ->orWhere('clave', 'like', "%{$search}%");
                  });
            });
        }

        $this->alumnos = $query->get()->toArray();
        $this->alumnoCount = count($this->alumnos);
    }

    public function updatedSearch()
    {
        $this->cargarAlumnos();
    }

    public function abrirModalCrear()
    {
        $this->editingAlumnoId = null;
        $this->reset(['id_carrera', 'no_control', 'nombre', 'apellido_paterno', 'apellido_materno', 'email']);
        $this->showModal = true;
    }

    public function abrirModalEditar($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);

        $this->editingAlumnoId = $alumnoId;
        $this->id_carrera        = $alumno->id_carrera;
        $this->no_control        = $alumno->no_control;
        $this->nombre            = $alumno->nombre;
        $this->apellido_paterno  = $alumno->apellido_paterno;
        $this->apellido_materno  = $alumno->apellido_materno;
        $this->email             = $alumno->email;
        $this->showModal = true;
    }

    public function guardar()
    {
        $this->validate([
            'id_carrera'       => 'required|exists:carreras,id_carrera',
            'no_control'       => 'required|min:3|max:20' . ($this->editingAlumnoId ? '|unique:alumnos,no_control,' . $this->editingAlumnoId . ',id_alumno' : '|unique:alumnos,no_control'),
            'nombre'           => 'required|min:2|max:50',
            'apellido_paterno' => 'required|min:2|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'email'            => 'required|email|max:100' . ($this->editingAlumnoId ? '|unique:alumnos,email,' . $this->editingAlumnoId . ',id_alumno' : '|unique:alumnos,email'),
        ]);

        $data = [
            'id_carrera'       => $this->id_carrera,
            'no_control'       => $this->no_control,
            'nombre'           => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'email'            => $this->email,
        ];

        if ($this->editingAlumnoId) {
            Alumno::findOrFail($this->editingAlumnoId)->update($data);
        } else {
            Alumno::create($data + ['activo' => 1]);
        }

        $isEditing = $this->editingAlumnoId;

        $this->showModal = false;
        $this->reset(['id_carrera', 'no_control', 'nombre', 'apellido_paterno', 'apellido_materno', 'email']);
        $this->editingAlumnoId = null;
        $this->cargarAlumnos();

        session()->flash('success', $isEditing
            ? 'Alumno actualizado correctamente.'
            : 'Alumno registrado exitosamente.');
    }

    public function eliminar($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);
        $alumno->delete();
        $this->cargarAlumnos();

        session()->flash('success', 'Alumno eliminado correctamente.');
    }

    public function toggleActivo($alumnoId)
    {
        $alumno = Alumno::findOrFail($alumnoId);
        $alumno->activo = !$alumno->activo;
        $alumno->save();
        $this->cargarAlumnos();
    }

    public function render()
    {
        return view('livewire.gestion-alumnos');
    }
}
