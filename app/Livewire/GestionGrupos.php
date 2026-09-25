<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Docente;
use App\Models\PeriodoEscolar;

class GestionGrupos extends Component
{
    public $grupos = [];
    public $search = '';
    public $id_materia = '';
    public $id_docente = '';
    public $id_periodo = '';
    public $nombre = '';
    public $cupo_maximo = 30;
    public $editingGrupoId = null;
    public $showModal = false;
    public $grupoCount = 0;

    // Selects
    public $materias = [];
    public $docentes = [];
    public $periodos = [];

    public function mount()
    {
        $this->cargarGrupos();
        $this->cargarSelects();
    }

    public function cargarSelects()
    {
        $this->materias = Materia::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_materia', 'clave', 'nombre'])
            ->toArray();

        $this->docentes = Docente::where('activo', 1)
            ->orderBy('apellido_paterno')
            ->get(['id_docente', 'nombre', 'apellido_paterno', 'apellido_materno'])
            ->toArray();

        $this->periodos = PeriodoEscolar::orderBy('clave', 'desc')
            ->get(['id_periodo', 'clave', 'nombre'])
            ->toArray();
    }

    public function cargarGrupos()
    {
        $query = Grupo::with(['materia', 'docente', 'periodo']);

        if ($this->search) {
            $search = $this->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhereHas('materia', function ($qm) use ($search) {
                      $qm->where('nombre', 'like', "%{$search}%")
                         ->orWhere('clave', 'like', "%{$search}%");
                  })
                  ->orWhereHas('docente', function ($qd) use ($search) {
                      $qd->where('nombre', 'like', "%{$search}%")
                         ->orWhere('apellido_paterno', 'like', "%{$search}%")
                         ->orWhere('apellido_materno', 'like', "%{$search}%");
                  })
                  ->orWhereHas('periodo', function ($qp) use ($search) {
                      $qp->where('clave', 'like', "%{$search}%")
                         ->orWhere('nombre', 'like', "%{$search}%");
                  });
            });
        }

        $this->grupos = $query->get()->toArray();
        $this->grupoCount = count($this->grupos);
    }

    public function updatedSearch()
    {
        $this->cargarGrupos();
    }

    public function abrirModalCrear()
    {
        $this->editingGrupoId = null;
        $this->reset(['id_materia', 'id_docente', 'id_periodo', 'nombre', 'cupo_maximo']);
        $this->cupo_maximo = 30;
        $this->showModal = true;
    }

    public function abrirModalEditar($grupoId)
    {
        $grupo = Grupo::findOrFail($grupoId);

        $this->editingGrupoId = $grupoId;
        $this->id_materia    = $grupo->id_materia;
        $this->id_docente    = $grupo->id_docente;
        $this->id_periodo    = $grupo->id_periodo;
        $this->nombre        = $grupo->nombre;
        $this->cupo_maximo   = $grupo->cupo_maximo;
        $this->showModal = true;
    }

    public function guardar()
    {
        $this->validate([
            'id_materia'  => 'required|exists:materias,id_materia',
            'id_docente'  => 'nullable|exists:docentes,id_docente',
            'id_periodo'  => 'required|exists:periodo_escolars,id_periodo',
            'nombre'      => 'required|min:1|max:20',
            'cupo_maximo' => 'required|integer|min:1|max:100',
        ]);

        $data = [
            'id_materia'  => $this->id_materia,
            'id_docente'  => $this->id_docente ?: null,
            'id_periodo'  => $this->id_periodo,
            'nombre'      => $this->nombre,
            'cupo_maximo' => $this->cupo_maximo,
        ];

        if ($this->editingGrupoId) {
            Grupo::findOrFail($this->editingGrupoId)->update($data);
        } else {
            Grupo::create($data + ['activo' => 1]);
        }

        $isEditing = $this->editingGrupoId;

        $this->showModal = false;
        $this->reset(['id_materia', 'id_docente', 'id_periodo', 'nombre', 'cupo_maximo']);
        $this->cupo_maximo = 30;
        $this->editingGrupoId = null;
        $this->cargarGrupos();

        session()->flash('success', $isEditing
            ? 'Grupo actualizado correctamente.'
            : 'Grupo registrado exitosamente.');
    }

    public function eliminar($grupoId)
    {
        $grupo = Grupo::findOrFail($grupoId);
        $grupo->delete();
        $this->cargarGrupos();

        session()->flash('success', 'Grupo eliminado correctamente.');
    }

    public function toggleActivo($grupoId)
    {
        $grupo = Grupo::findOrFail($grupoId);
        $grupo->activo = !$grupo->activo;
        $grupo->save();
        $this->cargarGrupos();
    }

    public function render()
    {
        return view('livewire.gestion-grupos');
    }
}
