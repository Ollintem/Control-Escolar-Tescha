<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Semestre;
use App\Models\PeriodoEscolar;
use App\Models\Carrera;

class GestionSemestresPeriodos extends Component
{
    // Pestaña activa: 'semestres' | 'periodos'
    public $tab = 'semestres';

    // ---- Semestres ----
    public $semestres = [];
    public $semestreSearch = '';
    public $id_carrera = '';
    public $numero = '';
    public $descripcion = '';
    public $editingSemestreId = null;
    public $showModalSemestre = false;
    public $semestreCount = 0;

    // ---- Periodos Escolares ----
    public $periodos = [];
    public $periodoSearch = '';
    public $clave = '';
    public $nombre = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';
    public $editingPeriodoId = null;
    public $showModalPeriodo = false;
    public $periodoCount = 0;

    // Selects
    public $carreras = [];

    public function mount()
    {
        $this->cargarSemestres();
        $this->cargarPeriodos();
        $this->cargarSelects();
    }

    public function cargarSelects()
    {
        $this->carreras = Carrera::where('activo', 1)
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();
    }

    /* ============================================================
       PESTAÑAS
    ============================================================ */
    public function switchTab($tab)
    {
        $this->tab = $tab;
    }

    /* ============================================================
       SEMESTRES
    ============================================================ */
    public function cargarSemestres()
    {
        $query = Semestre::with('carrera');

        if ($this->semestreSearch) {
            $search = $this->semestreSearch;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%")
                  ->orWhereHas('carrera', function ($qc) use ($search) {
                      $qc->where('nombre', 'like', "%{$search}%")
                         ->orWhere('clave', 'like', "%{$search}%");
                  });
            });
        }

        $this->semestres = $query->get()->toArray();
        $this->semestreCount = count($this->semestres);
    }

    public function updatedSemestreSearch()
    {
        $this->cargarSemestres();
    }

    public function abrirModalSemestreCrear()
    {
        $this->editingSemestreId = null;
        $this->reset(['id_carrera', 'numero', 'descripcion']);
        $this->showModalSemestre = true;
    }

    public function abrirModalSemestreEditar($semestreId)
    {
        $semestre = Semestre::findOrFail($semestreId);

        $this->editingSemestreId = $semestreId;
        $this->id_carrera    = $semestre->id_carrera;
        $this->numero        = $semestre->numero;
        $this->descripcion   = $semestre->descripcion;
        $this->showModalSemestre = true;
    }

    public function guardarSemestre()
    {
        $this->validate([
            'id_carrera'  => 'required|exists:carreras,id_carrera',
            'numero'      => 'required|integer|min:1|max:9',
            'descripcion' => 'nullable|string|max:60',
        ]);

        if ($this->editingSemestreId) {
            $semestre = Semestre::findOrFail($this->editingSemestreId);
            $semestre->update([
                'id_carrera'    => $this->id_carrera,
                'numero'        => $this->numero,
                'descripcion'   => $this->descripcion,
            ]);
        } else {
            Semestre::create([
                'id_carrera'    => $this->id_carrera,
                'numero'        => $this->numero,
                'descripcion'   => $this->descripcion,
            ]);
        }

        $isEditing = $this->editingSemestreId;

        $this->showModalSemestre = false;
        $this->reset(['id_carrera', 'numero', 'descripcion']);
        $this->editingSemestreId = null;
        $this->cargarSemestres();

        session()->flash('success', $isEditing
            ? 'Semestre actualizado correctamente.'
            : 'Semestre registrado exitosamente.');
    }

    public function eliminarSemestre($semestreId)
    {
        $semestre = Semestre::findOrFail($semestreId);
        $semestre->delete();
        $this->cargarSemestres();

        session()->flash('success', 'Semestre eliminado correctamente.');
    }

    /* ============================================================
       PERIODOS ESCOLARES
    ============================================================ */
    public function cargarPeriodos()
    {
        $query = PeriodoEscolar::query();

        if ($this->periodoSearch) {
            $query->where(function ($q) {
                $q->where('clave', 'like', "%{$this->periodoSearch}%")
                  ->orWhere('nombre', 'like', "%{$this->periodoSearch}%");
            });
        }

        $this->periodos = $query->get()->toArray();
        $this->periodoCount = count($this->periodos);
    }

    public function updatedPeriodoSearch()
    {
        $this->cargarPeriodos();
    }

    public function abrirModalPeriodoCrear()
    {
        $this->editingPeriodoId = null;
        $this->reset(['clave', 'nombre', 'fecha_inicio', 'fecha_fin']);
        $this->showModalPeriodo = true;
    }

    public function abrirModalPeriodoEditar($periodoId)
    {
        $periodo = PeriodoEscolar::findOrFail($periodoId);

        $this->editingPeriodoId = $periodoId;
        $this->clave        = $periodo->clave;
        $this->nombre       = $periodo->nombre;
        $this->fecha_inicio = $periodo->fecha_inicio;
        $this->fecha_fin    = $periodo->fecha_fin;
        $this->showModalPeriodo = true;
    }

    public function guardarPeriodo()
    {
        $this->validate([
            'clave'        => 'required|min:2|max:20' . ($this->editingPeriodoId ? '|unique:periodo_escolars,clave,' . $this->editingPeriodoId . ',id_periodo' : '|unique:periodo_escolars,clave'),
            'nombre'       => 'required|min:3|max:50',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin'    => 'nullable|date|after_or_equal:fecha_inicio',
        ]);

        if ($this->editingPeriodoId) {
            $periodo = PeriodoEscolar::findOrFail($this->editingPeriodoId);
            $periodo->update([
                'clave'        => $this->clave,
                'nombre'       => $this->nombre,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin'    => $this->fecha_fin,
            ]);
        } else {
            PeriodoEscolar::create([
                'clave'        => $this->clave,
                'nombre'       => $this->nombre,
                'fecha_inicio' => $this->fecha_inicio,
                'fecha_fin'    => $this->fecha_fin,
                'activo'       => 1,
            ]);
        }

        $isEditing = $this->editingPeriodoId;

        $this->showModalPeriodo = false;
        $this->reset(['clave', 'nombre', 'fecha_inicio', 'fecha_fin']);
        $this->editingPeriodoId = null;
        $this->cargarPeriodos();

        session()->flash('success', $isEditing
            ? 'Periodo actualizado correctamente.'
            : 'Periodo registrado exitosamente.');
    }

    public function eliminarPeriodo($periodoId)
    {
        $periodo = PeriodoEscolar::findOrFail($periodoId);
        $periodo->delete();
        $this->cargarPeriodos();

        session()->flash('success', 'Periodo eliminado correctamente.');
    }

    public function render()
    {
        return view('livewire.gestion-semestres-periodos');
    }
}
