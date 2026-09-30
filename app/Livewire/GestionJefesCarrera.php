<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JefeCarrera;
use App\Models\Carrera;

class GestionJefesCarrera extends Component
{
    public $jefes = [];
    public $carreras = [];
    public $search = '';

    // Datos personales del Jefe de Carrera (se registran directamente)
    public $nombre = '';
    public $apellido_paterno = '';
    public $apellido_materno = '';
    public $no_empleado = '';
    public $email = '';

    public $id_carrera = '';
    public $fecha_inicio = '';
    public $editingJefeId = null;
    public $showModal = false;
    public $jefeCount = 0;

    public function mount()
    {
        $this->cargarJefes();
        $this->cargarSelects();
    }

    public function cargarSelects()
    {
        $this->carreras = Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();
    }

    public function cargarJefes()
    {
        $query = JefeCarrera::with('carrera');

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('apellido_paterno', 'like', "%{$search}%")
                  ->orWhere('apellido_materno', 'like', "%{$search}%")
                  ->orWhere('no_empleado', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('carrera', function ($qc) use ($search) {
                      $qc->where('nombre', 'like', "%{$search}%")
                         ->orWhere('clave', 'like', "%{$search}%");
                  });
            });
        }

        $this->jefes = $query->get()->toArray();
        $this->jefeCount = count($this->jefes);
    }

    public function updatedSearch()
    {
        $this->cargarJefes();
    }

    public function abrirModalCrear()
    {
        $this->editingJefeId = null;
        $this->reset(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email', 'id_carrera', 'fecha_inicio']);
        $this->showModal = true;
    }

    public function abrirModalEditar($jefeId)
    {
        $jefe = JefeCarrera::findOrFail($jefeId);

        $this->editingJefeId    = $jefeId;
        $this->nombre           = $jefe->nombre;
        $this->apellido_paterno = $jefe->apellido_paterno;
        $this->apellido_materno = $jefe->apellido_materno;
        $this->no_empleado      = $jefe->no_empleado;
        $this->email            = $jefe->email;
        $this->id_carrera       = $jefe->id_carrera;
        $this->fecha_inicio     = $jefe->fecha_inicio;
        $this->showModal = true;
    }

    public function guardar()
    {
        $this->validate([
            'nombre'           => 'required|min:2|max:50',
            'apellido_paterno' => 'required|min:2|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'no_empleado'      => 'required|min:1|max:20' . ($this->editingJefeId ? '|unique:jefes_carrera,no_empleado,' . $this->editingJefeId . ',id_jefe_carrera' : '|unique:jefes_carrera,no_empleado'),
            'email'            => 'required|email|max:100' . ($this->editingJefeId ? '|unique:jefes_carrera,email,' . $this->editingJefeId . ',id_jefe_carrera' : '|unique:jefes_carrera,email'),
            'id_carrera'       => 'required|exists:carreras,id_carrera',
            'fecha_inicio'     => 'required|date',
        ]);

        $datos = [
            'nombre'           => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'no_empleado'      => $this->no_empleado,
            'email'            => $this->email,
            'id_carrera'       => $this->id_carrera,
            'fecha_inicio'     => $this->fecha_inicio,
        ];

        if ($this->editingJefeId) {
            JefeCarrera::findOrFail($this->editingJefeId)->update($datos);
        } else {
            JefeCarrera::create($datos + ['activo' => 1]);
        }

        $isEditing = $this->editingJefeId;

        $this->showModal = false;
        $this->reset(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email', 'id_carrera', 'fecha_inicio']);
        $this->editingJefeId = null;
        $this->cargarJefes();

        session()->flash('success', $isEditing
            ? 'Jefe de carrera actualizado correctamente.'
            : 'Jefe de carrera registrado exitosamente.');
    }

    public function eliminar($jefeId)
    {
        $jefe = JefeCarrera::findOrFail($jefeId);
        $jefe->delete();
        $this->cargarJefes();

        session()->flash('success', 'Jefe de carrera eliminado correctamente.');
    }

    public function render()
    {
        return view('admin.jefes-carrera.index');
    }
}
