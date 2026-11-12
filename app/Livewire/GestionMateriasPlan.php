<?php

namespace App\Livewire;

use App\Models\Carrera;
use App\Models\Materia;
use App\Models\PlanEstudio;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

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

    public function mount(): void
    {
        $this->cargarMaterias();
        $this->cargarPlanes();
        $this->cargarSelects();
    }

    public function cargarSelects(?int $carreraActualId = null): void
    {
        $this->exigirPermiso('ver');

        $query = Carrera::where('activo', 1);

        if ($carreraActualId !== null) {
            $query->orWhere('id_carrera', $carreraActualId);
        }

        $this->carreras = $query
            ->orderBy('nombre')
            ->get(['id_carrera', 'clave', 'nombre'])
            ->toArray();
    }

    /* ============================================================
       PESTAÑAS
    ============================================================ */
    public function switchTab(string $tab): void
    {
        $this->tab = $tab;
    }

    /* ============================================================
       MATERIAS
    ============================================================ */
    public function cargarMaterias(): void
    {
        $this->exigirPermiso('ver');

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

    public function updatedMateriaSearch(): void
    {
        $this->cargarMaterias();
    }

    public function abrirModalMateriaCrear(): void
    {
        $this->exigirPermiso('crear');

        $this->editingMateriaId = null;
        $this->reset(['m_id_carrera', 'm_clave', 'm_nombre', 'm_creditos', 'm_horas_teoricas', 'm_horas_practicas']);
        $this->m_horas_teoricas = 0;
        $this->m_horas_practicas = 0;
        $this->cargarSelects();
        $this->showModalMateria = true;
    }

    public function abrirModalMateriaEditar(int $materiaId): void
    {
        $this->exigirPermiso('editar');

        $materia = Materia::findOrFail($materiaId);

        $this->editingMateriaId = $materiaId;
        $this->m_id_carrera = $materia->id_carrera;
        $this->m_clave = $materia->clave;
        $this->m_nombre = $materia->nombre;
        $this->m_creditos = $materia->creditos;
        $this->m_horas_teoricas = $materia->horas_teoricas;
        $this->m_horas_practicas = $materia->horas_practicas;
        $this->cargarSelects($materia->id_carrera);
        $this->showModalMateria = true;
    }

    public function guardarMateria(): void
    {
        $isEditing = $this->editingMateriaId !== null && $this->editingMateriaId !== '';
        $this->exigirPermiso($isEditing ? 'editar' : 'crear');

        $materia = $isEditing ? Materia::findOrFail($this->editingMateriaId) : null;
        $isKeepingCurrentCareer = $materia !== null
            && (string) $this->m_id_carrera === (string) $materia->id_carrera;

        $this->validate([
            'm_id_carrera' => [
                'required',
                $isKeepingCurrentCareer
                    ? Rule::exists('carreras', 'id_carrera')
                    : Rule::exists('carreras', 'id_carrera')->where('activo', 1),
            ],
            'm_clave' => ['required', 'min:2', 'max:20', Rule::unique('materias', 'clave')->ignore($materia?->id_materia, 'id_materia')],
            'm_nombre' => 'required|min:3|max:100',
            'm_creditos' => 'required|integer|min:1|max:20',
            'm_horas_teoricas' => 'nullable|integer|min:0|max:20',
            'm_horas_practicas' => 'nullable|integer|min:0|max:20',
        ]);

        $data = [
            'id_carrera' => $this->m_id_carrera,
            'clave' => $this->m_clave,
            'nombre' => $this->m_nombre,
            'creditos' => $this->m_creditos,
            'horas_teoricas' => $this->m_horas_teoricas ?: 0,
            'horas_practicas' => $this->m_horas_practicas ?: 0,
        ];

        if ($isEditing) {
            $materia->update($data);
        } else {
            Materia::create($data + ['activo' => 1]);
        }

        $this->showModalMateria = false;
        $this->reset(['m_id_carrera', 'm_clave', 'm_nombre', 'm_creditos', 'm_horas_teoricas', 'm_horas_practicas']);
        $this->editingMateriaId = null;

        if (Gate::allows('Materias y Plan de Estudios.ver')) {
            $this->cargarMaterias();
        }

        session()->flash('success', $isEditing
            ? 'Materia actualizada correctamente.'
            : 'Materia registrada exitosamente.');
    }

    public function eliminarMateria(int $materiaId): void
    {
        $this->exigirPermiso('eliminar');

        session()->flash('error', 'La eliminación de materias está suspendida para proteger la información académica relacionada.');
    }

    /* ============================================================
       PLANES DE ESTUDIO
    ============================================================ */
    public function cargarPlanes(): void
    {
        $this->exigirPermiso('ver');

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

    public function updatedPlanSearch(): void
    {
        $this->cargarPlanes();
    }

    public function abrirModalPlanCrear(): void
    {
        $this->exigirPermiso('crear');

        $this->editingPlanId = null;
        $this->reset(['p_id_carrera', 'p_clave', 'p_nombre', 'p_anio_publicacion']);
        $this->cargarSelects();
        $this->showModalPlan = true;
    }

    public function abrirModalPlanEditar(int $planId): void
    {
        $this->exigirPermiso('editar');

        $plan = PlanEstudio::findOrFail($planId);

        $this->editingPlanId = $planId;
        $this->p_id_carrera = $plan->id_carrera;
        $this->p_clave = $plan->clave;
        $this->p_nombre = $plan->nombre;
        $this->p_anio_publicacion = $plan->anio_publicacion;
        $this->cargarSelects($plan->id_carrera);
        $this->showModalPlan = true;
    }

    public function guardarPlan(): void
    {
        $isEditing = $this->editingPlanId !== null && $this->editingPlanId !== '';
        $this->exigirPermiso($isEditing ? 'editar' : 'crear');

        $plan = $isEditing ? PlanEstudio::findOrFail($this->editingPlanId) : null;
        $isKeepingCurrentCareer = $plan !== null
            && (string) $this->p_id_carrera === (string) $plan->id_carrera;

        $this->validate([
            'p_id_carrera' => [
                'required',
                $isKeepingCurrentCareer
                    ? Rule::exists('carreras', 'id_carrera')
                    : Rule::exists('carreras', 'id_carrera')->where('activo', 1),
            ],
            'p_clave' => ['required', 'min:2', 'max:30', Rule::unique('plan_estudios', 'clave')->ignore($plan?->id_plan_estudio, 'id_plan_estudio')],
            'p_nombre' => 'required|min:3|max:100',
            'p_anio_publicacion' => 'nullable|integer|min:1900|max:2100',
        ]);

        $data = [
            'id_carrera' => $this->p_id_carrera,
            'clave' => $this->p_clave,
            'nombre' => $this->p_nombre,
            'anio_publicacion' => $this->p_anio_publicacion ?: null,
        ];

        if ($isEditing) {
            $plan->update($data);
        } else {
            PlanEstudio::create($data + ['activo' => 1]);
        }

        $this->showModalPlan = false;
        $this->reset(['p_id_carrera', 'p_clave', 'p_nombre', 'p_anio_publicacion']);
        $this->editingPlanId = null;

        if (Gate::allows('Materias y Plan de Estudios.ver')) {
            $this->cargarPlanes();
        }

        session()->flash('success', $isEditing
            ? 'Plan de estudios actualizado correctamente.'
            : 'Plan de estudios registrado exitosamente.');
    }

    public function eliminarPlan(int $planId): void
    {
        $this->exigirPermiso('eliminar');

        try {
            $deleted = DB::transaction(function () use ($planId): bool {
                $plan = PlanEstudio::query()->lockForUpdate()->findOrFail($planId);

                return $plan->delete();
            });
        } catch (ModelNotFoundException) {
            session()->flash('error', 'No se encontró el plan de estudios que se intentó eliminar.');

            return;
        } catch (QueryException) {
            session()->flash('error', 'No se pudo eliminar el plan de estudios porque existe información relacionada o una restricción de integridad lo impide.');

            return;
        }

        if (! $deleted) {
            session()->flash('error', 'No se pudo eliminar el plan de estudios. El registro se conservó.');

            return;
        }

        if (Gate::allows('Materias y Plan de Estudios.ver')) {
            $this->cargarPlanes();
        }

        session()->flash('success', 'Plan de estudios eliminado correctamente.');
    }

    public function render(): View
    {
        $this->exigirPermiso('ver');

        return view('admin.materias-plan.index');
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(Gate::allows('Materias y Plan de Estudios.'.$accion), 403);
    }
}
