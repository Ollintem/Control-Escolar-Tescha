<?php

namespace App\Livewire;

use App\Models\Carrera;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\On;
use Livewire\Component;

class GestionCarreras extends Component
{
    public $carreras = [];

    public $search = '';

    public $clave = '';

    public $nombre = '';

    public $reticula_oficial = '';

    public $activo = true;

    public $editingCarreraId = null;

    public $showModal = false;

    public $carreraCount = 0;

    // Panel de "Administración de la carrera" (solo lectura).
    // Se abre desde el Dashboard al hacer clic en el nombre o en la flecha.
    public $carreraDetalle = null;

    public function mount()
    {
        $this->cargarCarreras();
    }

    public function cargarCarreras()
    {
        $this->exigirPermiso('ver');

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
        $this->exigirPermiso('crear');

        $this->editingCarreraId = null;
        $this->reset(['clave', 'nombre', 'reticula_oficial']);
        $this->activo = true;
        $this->showModal = true;
    }

    public function abrirModalEditar($carreraId)
    {
        $this->exigirPermiso('editar');

        $carrera = Carrera::findOrFail($carreraId);

        $this->editingCarreraId = $carreraId;
        $this->clave = $carrera->clave;
        $this->nombre = $carrera->nombre;
        $this->reticula_oficial = $carrera->reticula_oficial;
        $this->activo = (bool) $carrera->activo;
        $this->showModal = true;
    }

    /* ============================================================
       ADMINISTRACIÓN DE LA CARRERA (acceso desde el Dashboard)
       ============================================================
       No crea rutas ni controladores nuevos: reutiliza este módulo y
       muestra la información general de la carrera seleccionada más los
       conteos de sus relaciones ya existentes en la base de datos.
    ============================================================ */

    #[On('ver-carrera')]
    public function verCarreraDesdeDashboard($carreraId)
    {
        $this->verCarrera($carreraId);
    }

    public function verCarrera($carreraId)
    {
        $this->exigirPermiso('ver');

        $carrera = Carrera::findOrFail($carreraId);

        $this->carreraDetalle = [
            'id_carrera' => $carrera->id_carrera,
            'clave' => $carrera->clave,
            'nombre' => $carrera->nombre,
            'reticula_oficial' => $carrera->reticula_oficial,
            'activo' => (bool) $carrera->activo,
            'semestres' => DB::table('semestres')->where('id_carrera', $carreraId)->count(),
            'materias' => DB::table('materias')->where('id_carrera', $carreraId)->count(),
            'jefes' => DB::table('jefes_carrera')->where('id_carrera', $carreraId)->count(),
            'alumnos' => DB::table('alumnos')->where('id_carrera', $carreraId)->count(),
            'grupos' => DB::table('grupos')
                ->join('materias', 'materias.id_materia', '=', 'grupos.id_materia')
                ->where('materias.id_carrera', $carreraId)
                ->count(),
            'docentes' => DB::table('carrera_docente')->where('id_carrera', $carreraId)->count(),
        ];

        // Limpia la búsqueda para que la carrera elegida siempre aparezca
        // en el catálogo de abajo.
        $this->search = '';
        $this->cargarCarreras();
    }

    public function cerrarDetalleCarrera()
    {
        $this->exigirPermiso('ver');

        $this->carreraDetalle = null;
    }

    /** "Editar" del catálogo → formulario de datos básicos (no materias). */
    #[On('editar-carrera')]
    public function editarCarreraDesdeDashboard($carreraId)
    {
        $this->abrirModalEditar($carreraId);
    }

    public function guardar()
    {
        $esEdicion = (bool) $this->editingCarreraId;
        $this->exigirPermiso($esEdicion ? 'editar' : 'crear');

        $this->validate([
            'clave' => 'required|min:2|max:20'.($this->editingCarreraId ? '|unique:carreras,clave,'.$this->editingCarreraId.',id_carrera' : '|unique:carreras,clave'),
            'nombre' => 'required|min:3|max:100',
            'reticula_oficial' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        if ($esEdicion) {
            $carrera = Carrera::findOrFail($this->editingCarreraId);
            $carrera->update([
                'clave' => $this->clave,
                'nombre' => $this->nombre,
                'reticula_oficial' => $this->reticula_oficial,
                'activo' => $this->activo ? 1 : 0,
            ]);
        } else {
            Carrera::create([
                'clave' => $this->clave,
                'nombre' => $this->nombre,
                'reticula_oficial' => $this->reticula_oficial,
                'activo' => $this->activo ? 1 : 0,
            ]);
        }

        $isEditing = $this->editingCarreraId;

        $this->showModal = false;
        $this->reset(['clave', 'nombre', 'reticula_oficial']);
        $this->activo = true;
        $this->editingCarreraId = null;
        $this->cargarCarreras();

        // Si el panel de detalle mostraba esta carrera, se refresca para
        // que no quede desactualizada.
        if ($this->carreraDetalle && (int) $this->carreraDetalle['id_carrera'] === (int) $isEditing) {
            $this->verCarrera($isEditing);
        }

        session()->flash('success', $isEditing
            ? 'Carrera actualizada correctamente.'
            : 'Carrera registrada exitosamente.');
    }

    public function eliminar($carreraId)
    {
        $this->exigirPermiso('eliminar');

        session()->flash(
            'error',
            'La eliminación de carreras está temporalmente suspendida para proteger la información académica.'
        );
    }

    public function render()
    {
        $this->exigirPermiso('ver');

        return view('admin.carreras.index', [
            'puedeCrear' => Gate::allows('Carreras y Semestres.crear'),
            'puedeEditar' => Gate::allows('Carreras y Semestres.editar'),
        ]);
    }

    private function exigirPermiso(string $accion): void
    {
        abort_unless(
            Gate::allows('Carreras y Semestres.'.$accion),
            403,
            'No tienes permiso para realizar esta acción.'
        );
    }
}
