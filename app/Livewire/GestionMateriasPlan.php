<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Materia;
use App\Models\PlanEstudio;
use App\Models\Carrera;

class GestionMateriasPlan extends Component
{
    // Pestaña activa: 'materias' | 'planes'
    public $tab = 'materias';

    // ---- Materias ----
    public $materias = [];
    public $materiaSearch = '';
    public $m_id_carrera = '';
    public $m_clave = '';
    public $m_nombre = '';
    public $m_creditos = '';
    public $m_horas_teoricas = 0;
    public $m_horas_practicas = 0;
    public $editingMateriaId = null;
    public $showModalMateria = false;
    public $materiaCount = 0;

    // ---- Planes de Estudio ----
    public $planes = [];
    public $planSearch = '';
    public $p_id_carrera = '';
    public $p_clave = '';
    public $p_nombre = '';
    public $p_anio_publicacion = '';
    public $editingPlanId = null;
    public $showModalPlan = false;
    public $planCount = 0;

    // Selects
    public $carreras = [];

    public function mount()
    {
        $this->cargarMaterias();
        $this->cargarPlanes();
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
       MATERIAS
    ============================================================ */
    public function cargarMaterias()
    {
        $query = Materia::with('carrera');

        if ($this->materiaSearch) {
            $search = $this->materiaSearch;
            $query->where(function ($q) use ($search) {
                $q->where('clave', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhereHas('carrera', function ($qc) use ($search) {
                      $qc->where('nombre', 'like', "%{$search}%")
                         ->orWhere('clave', 'like', "%{$search}%");
                  });
            });
        }

        $this->materias = $query->get()->toArray();
        $this->materiaCount = count($this->materias);
    }

    public function updatedMateriaSearch()
    {
        $this->cargarMaterias();
    }

    public function abrirModalMateriaCrear()
    {
        $this->editingMateriaId = null;
        $this->reset(['m_id_carrera', 'm_clave', 'm_nombre', 'm_creditos', 'm_horas_teoricas', 'm_horas_practicas']);
        $this->m_horas_teoricas = 0;
        $this->m_horas_practicas = 0;
        $this->showModalMateria = true;
    }

    public function abrirModalMateriaEditar($materiaId)
    {
        $materia = Materia::findOrFail($materiaId);

        $this->editingMateriaId = $materiaId;
        $this->m_id_carrera       = $materia->id_carrera;
        $this->m_clave            = $materia->clave;
        $this->m_nombre           = $materia->nombre;
        $this->m_creditos         = $materia->creditos;
        $this->m_horas_teoricas   = $materia->horas_teoricas;
        $this->m_horas_practicas  = $materia->horas_practicas;
        $this->showModalMateria = true;
    }

    public function guardarMateria()
    {
        $this->validate([
            'm_id_carrera'      => 'required|exists:carreras,id_carrera',
            'm_clave'           => 'required|min:2|max:20' . ($this->editingMateriaId ? '|unique:materias,clave,' . $this->editingMateriaId . ',id_materia' : '|unique:materias,clave'),
            'm_nombre'          => 'required|min:3|max:100',
            'm_creditos'        => 'required|integer|min:1|max:20',
            'm_horas_teoricas'  => 'nullable|integer|min:0|max:20',
            'm_horas_practicas' => 'nullable|integer|min:0|max:20',
        ]);

        $data = [
            'id_carrera'      => $this->m_id_carrera,
            'clave'           => $this->m_clave,
            'nombre'          => $this->m_nombre,
            'creditos'        => $this->m_creditos,
            'horas_teoricas'  => $this->m_horas_teoricas ?: 0,
            'horas_practicas' => $this->m_horas_practicas ?: 0,
        ];

        if ($this->editingMateriaId) {
            Materia::findOrFail($this->editingMateriaId)->update($data);
        } else {
            Materia::create($data + ['activo' => 1]);
        }

        $isEditing = $this->editingMateriaId;

        $this->showModalMateria = false;
        $this->reset(['m_id_carrera', 'm_clave', 'm_nombre', 'm_creditos', 'm_horas_teoricas', 'm_horas_practicas']);
        $this->editingMateriaId = null;
        $this->cargarMaterias();

        session()->flash('success', $isEditing
            ? 'Materia actualizada correctamente.'
            : 'Materia registrada exitosamente.');
    }

    public function eliminarMateria($materiaId)
    {
        $materia = Materia::findOrFail($materiaId);
        $materia->delete();
        $this->cargarMaterias();

        session()->flash('success', 'Materia eliminada correctamente.');
    }

    /* ============================================================
       PLANES DE ESTUDIO
    ============================================================ */
    public function cargarPlanes()
    {
        $query = PlanEstudio::with('carrera');

        if ($this->planSearch) {
            $search = $this->planSearch;
            $query->where(function ($q) use ($search) {
                $q->where('clave', 'like', "%{$search}%")
                  ->orWhere('nombre', 'like', "%{$search}%")
                  ->orWhere('anio_publicacion', 'like', "%{$search}%")
                  ->orWhereHas('carrera', function ($qc) use ($search) {
                      $qc->where('nombre', 'like', "%{$search}%")
                         ->orWhere('clave', 'like', "%{$search}%");
                  });
            });
        }

        $this->planes = $query->get()->toArray();
        $this->planCount = count($this->planes);
    }

    public function updatedPlanSearch()
    {
        $this->cargarPlanes();
    }

    public function abrirModalPlanCrear()
    {
        $this->editingPlanId = null;
        $this->reset(['p_id_carrera', 'p_clave', 'p_nombre', 'p_anio_publicacion']);
        $this->showModalPlan = true;
    }

    public function abrirModalPlanEditar($planId)
    {
        $plan = PlanEstudio::findOrFail($planId);

        $this->editingPlanId = $planId;
        $this->p_id_carrera        = $plan->id_carrera;
        $this->p_clave             = $plan->clave;
        $this->p_nombre            = $plan->nombre;
        $this->p_anio_publicacion  = $plan->anio_publicacion;
        $this->showModalPlan = true;
    }

    public function guardarPlan()
    {
        $this->validate([
            'p_id_carrera'       => 'required|exists:carreras,id_carrera',
            'p_clave'            => 'required|min:2|max:30' . ($this->editingPlanId ? '|unique:plan_estudios,clave,' . $this->editingPlanId . ',id_plan_estudio' : '|unique:plan_estudios,clave'),
            'p_nombre'           => 'required|min:3|max:100',
            'p_anio_publicacion' => 'nullable|integer|min:1900|max:2100',
        ]);

        $data = [
            'id_carrera'       => $this->p_id_carrera,
            'clave'            => $this->p_clave,
            'nombre'           => $this->p_nombre,
            'anio_publicacion' => $this->p_anio_publicacion ?: null,
        ];

        if ($this->editingPlanId) {
            PlanEstudio::findOrFail($this->editingPlanId)->update($data);
        } else {
            PlanEstudio::create($data + ['activo' => 1]);
        }

        $isEditing = $this->editingPlanId;

        $this->showModalPlan = false;
        $this->reset(['p_id_carrera', 'p_clave', 'p_nombre', 'p_anio_publicacion']);
        $this->editingPlanId = null;
        $this->cargarPlanes();

        session()->flash('success', $isEditing
            ? 'Plan de estudios actualizado correctamente.'
            : 'Plan de estudios registrado exitosamente.');
    }

    public function eliminarPlan($planId)
    {
        $plan = PlanEstudio::findOrFail($planId);
        $plan->delete();
        $this->cargarPlanes();

        session()->flash('success', 'Plan de estudios eliminado correctamente.');
    }

    public function render()
    {
        return view('livewire.gestion-materias-plan');
    }
}
