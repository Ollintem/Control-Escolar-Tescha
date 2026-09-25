<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\JefeCarrera;
use App\Models\Docente;
use App\Models\Carrera;

class GestionJefesCarrera extends Component
{
    public $jefes = [];
    public $docentes = [];
    public $carreras = [];
    public $search = '';
    public $id_docente = '';
    public $id_carrera = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';
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
        $this->docentes = Docente::where('activo', 1)
            ->orderBy('apellido_paterno')
            ->get(['id_docente', 'nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado'])
            ->toArray();

        $this->carreras = Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();
    }

    public function cargarJefes()
    {
        $query = JefeCarrera::with(['docente', 'carrera']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('docente', function ($qd) use ($search) {
                    $qd->where('nombre', 'like', "%{$search}%")
                       ->orWhere('apellido_paterno', 'like', "%{$search}%")
                       ->orWhere('apellido_materno', 'like', "%{$search}%")
                       ->orWhere('no_empleado', 'like', "%{$search}%");
                })->orWhereHas('carrera', function ($qc) use ($search) {
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
        $this->reset(['id_docente', 'id_carrera', 'fecha_inicio', 'fecha_fin']);
        $this->showModal = true;
    }

    public function abrirModalEditar($jefeId)
    {
        $jefe = JefeCarrera::findOrFail($jefeId);

        $this->editingJefeId = $jefeId;
        $this->id_docente   = $jefe->id_docente;
        $this->id_carrera   = $jefe->id_carrera;
        $this->fecha_inicio = $jefe->fecha_inicio;
        $this->fecha_fin    = $jefe->fecha_fin;
        $this->showModal = true;
    }

    public function guardar()
    {
        $this->validate([
            'id_docente'   => 'required|exists:docentes,id_docente',
            'id_carrera'   => 'required|exists:carreras,id_carrera',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        if ($this->editingJefeId) {
            $jefe = JefeCarrera::findOrFail($this->editingJefeId);
            $jefe->update([
                'id_docente'   => $this->id_docente,
                'id_carrera'   => $this->id_carrera,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin'    => $this->fecha_fin,
            ]);
        } else {
            JefeCarrera::create([
                'id_docente'   => $this->id_docente,
                'id_carrera'   => $this->id_carrera,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin'    => $this->fecha_fin,
                'activo'       => 1,
            ]);
        }

        $isEditing = $this->editingJefeId;

        $this->showModal = false;
        $this->reset(['id_docente', 'id_carrera', 'fecha_inicio', 'fecha_fin']);
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
        return view('livewire.gestion-jefes-carrera');
    }
}
