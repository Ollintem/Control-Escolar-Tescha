<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Carrera;

class GestionCarreras extends Component
{
    public $carreras = [];
    public $search = '';
    public $clave = '';
    public $nombre = '';
    public $reticula_oficial = '';
    public $editingCarreraId = null;
    public $showModal = false;
    public $carreraCount = 0;

    public function mount()
    {
        $this->cargarCarreras();
    }

    public function cargarCarreras()
    {
        $query = Carrera::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('clave', 'like', "%{$this->search}%")
                  ->orWhere('nombre', 'like', "%{$this->search}%")
                  ->orWhere('reticula_oficial', 'like', "%{$this->search}%");
            });
        }

        $this->carreras = $query->get()->toArray();
        $this->carreraCount = count($this->carreras);
    }

    public function updatedSearch()
    {
        $this->cargarCarreras();
    }

    public function abrirModalCrear()
    {
        $this->editingCarreraId = null;
        $this->reset(['clave', 'nombre', 'reticula_oficial']);
        $this->showModal = true;
    }

    public function abrirModalEditar($carreraId)
    {
        $carrera = Carrera::findOrFail($carreraId);

        $this->editingCarreraId = $carreraId;
        $this->clave = $carrera->clave;
        $this->nombre = $carrera->nombre;
        $this->reticula_oficial = $carrera->reticula_oficial;
        $this->showModal = true;
    }

    public function guardar()
    {
        $this->validate([
            'clave'             => 'required|min:2|max:20' . ($this->editingCarreraId ? '|unique:carreras,clave,' . $this->editingCarreraId . ',id_carrera' : '|unique:carreras,clave'),
            'nombre'            => 'required|min:3|max:100',
            'reticula_oficial'  => 'nullable|string|max:255',
        ]);

        if ($this->editingCarreraId) {
            $carrera = Carrera::findOrFail($this->editingCarreraId);
            $carrera->update([
                'clave'             => $this->clave,
                'nombre'            => $this->nombre,
                'reticula_oficial'  => $this->reticula_oficial,
            ]);
        } else {
            Carrera::create([
                'clave'             => $this->clave,
                'nombre'            => $this->nombre,
                'reticula_oficial'  => $this->reticula_oficial,
                'activo'            => 1,
            ]);
        }

        $isEditing = $this->editingCarreraId;

        $this->showModal = false;
        $this->reset(['clave', 'nombre', 'reticula_oficial']);
        $this->editingCarreraId = null;
        $this->cargarCarreras();

        session()->flash('success', $isEditing
            ? 'Carrera actualizada correctamente.'
            : 'Carrera registrada exitosamente.');
    }

    public function eliminar($carreraId)
    {
        $carrera = Carrera::findOrFail($carreraId);
        $carrera->delete();
        $this->cargarCarreras();

        session()->flash('success', 'Carrera eliminada correctamente.');
    }

    public function render()
    {
        return view('livewire.gestion-carreras');
    }
}
