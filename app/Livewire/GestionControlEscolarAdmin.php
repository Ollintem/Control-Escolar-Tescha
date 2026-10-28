<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class GestionControlEscolarAdmin extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public ?int $detalleUsuarioId = null;

    public function mount(): void
    {
        $this->autorizarAdministrador();
    }

    public function updatedBusqueda(): void
    {
        $this->autorizarAdministrador();
        $this->resetPage();
    }

    public function verDetalles(int $userId): void
    {
        $this->autorizarAdministrador();

        abort_unless(
            $this->personalControlEscolarQuery()->whereKey($userId)->exists(),
            404
        );

        $this->detalleUsuarioId = $this->detalleUsuarioId === $userId ? null : $userId;
    }

    /** Solicita el cambio de estado al módulo existente Usuarios y Roles. */
    public function cambiarEstado(int $userId): void
    {
        $this->autorizarAdministrador();

        abort_unless(
            $this->personalControlEscolarQuery()->whereKey($userId)->exists(),
            404
        );

        $this->dispatch('cambiar-estado-control-escolar', userId: $userId);
    }

    /** Actualiza el listado tras una operación del componente GestionUsuarios. */
    #[On('actualizar-personal-control-escolar')]
    public function actualizarPersonal(): void
    {
        $this->autorizarAdministrador();
    }

    public function render()
    {
        $this->autorizarAdministrador();

        $consultaPersonal = $this->personalControlEscolarQuery();
        $totalPersonal = (clone $consultaPersonal)->count();
        $cuentasActivas = (clone $consultaPersonal)->where('activo', 1)->count();
        $busqueda = trim($this->busqueda);

        $usuarios = (clone $consultaPersonal)
            ->when($busqueda !== '', function (Builder $query) use ($busqueda): void {
                $query->where(function (Builder $subquery) use ($busqueda): void {
                    $subquery->where('name', 'like', "%{$busqueda}%")
                        ->orWhere('email', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('name')
            ->paginate(12);

        $usuarioDetalle = $this->detalleUsuarioId !== null
            ? (clone $consultaPersonal)->whereKey($this->detalleUsuarioId)->first()
            : null;

        return view('admin.control-escolar.index', [
            'usuarios' => $usuarios,
            'totalPersonal' => $totalPersonal,
            'cuentasActivas' => $cuentasActivas,
            'usuarioDetalle' => $usuarioDetalle,
            'usuarioAutenticadoId' => Auth::id(),
        ]);
    }

    /** Consulta únicamente usuarios cuyo rol relacionado es Control Escolar. */
    private function personalControlEscolarQuery(): Builder
    {
        return User::query()
            ->with('role')
            ->whereHas('role', fn (Builder $query) => $query->where('nombre', 'Control Escolar'));
    }

    private function autorizarAdministrador(): void
    {
        abort_unless(Auth::user()?->esAdministrador(), 403);
    }
}
