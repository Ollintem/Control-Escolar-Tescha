<?php

namespace App\Livewire;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class MatrizPermisosDashboard extends Component
{
    public array $matriz = [];

    #[Locked]
    public array $roles = [];

    #[Locked]
    public array $modulos = [];

    #[Locked]
    public bool $guardado = false;

    #[Locked]
    public array $rolesIdsOriginales = [];

    #[Locked]
    public array $rolesEditablesIdsOriginales = [];

    #[Locked]
    public array $rolesAdministradorIdsOriginales = [];

    #[Locked]
    public array $permisosIdsOriginales = [];

    #[Locked]
    public array $asignacionesOriginales = [];

    public function mount(): void
    {
        $this->cargarMatriz();
    }

    public function cargarMatriz(): void
    {
        $this->autorizar('ver');

        $datosMatriz = DB::transaction(function (): array {
            $roles = Role::query()
                ->where('activo', true)
                ->orderBy('id_rol')
                ->lockForUpdate()
                ->get();
            $permisos = Permiso::query()
                ->orderBy('id_permiso')
                ->lockForUpdate()
                ->get();

            $rolesIds = $roles->pluck('id_rol')->map(static fn ($id): int => (int) $id)->all();
            $permisosIds = $permisos->pluck('id_permiso')->map(static fn ($id): int => (int) $id)->all();
            $asignaciones = array_fill_keys($rolesIds, []);

            if ($rolesIds !== []) {
                $filasAsignacion = DB::table('rol_permiso')
                    ->whereIn('FK_id_rol', $rolesIds)
                    ->orderBy('FK_id_rol')
                    ->orderBy('FK_id_permiso')
                    ->lockForUpdate()
                    ->get(['FK_id_rol', 'FK_id_permiso']);

                foreach ($filasAsignacion as $fila) {
                    $asignaciones[(int) $fila->FK_id_rol][] = (int) $fila->FK_id_permiso;
                }
            }

            $rolesParaVista = [];
            $rolesEditablesIds = [];
            $rolesAdministradorIds = [];

            foreach ($roles as $role) {
                $roleId = (int) $role->id_rol;
                $esAdministrador = $this->esRolAdministrador($role);

                $rolesParaVista[] = [
                    'id_rol' => $roleId,
                    'nombre' => $role->nombre,
                    'icono' => $esAdministrador ? 'shield-check' : 'shield',
                    'es_admin' => $esAdministrador,
                ];

                if ($esAdministrador) {
                    $rolesAdministradorIds[] = $roleId;

                    continue;
                }

                $rolesEditablesIds[] = $roleId;
            }

            return [
                'roles' => $rolesParaVista,
                'permisos' => $permisos->groupBy('modulo')->toArray(),
                'roles_ids' => $rolesIds,
                'roles_editables_ids' => $rolesEditablesIds,
                'roles_administrador_ids' => $rolesAdministradorIds,
                'permisos_ids' => $permisosIds,
                'asignaciones' => $asignaciones,
            ];
        });

        $this->roles = $datosMatriz['roles'];
        $this->modulos = $datosMatriz['permisos'];
        $this->rolesIdsOriginales = $datosMatriz['roles_ids'];
        $this->rolesEditablesIdsOriginales = $datosMatriz['roles_editables_ids'];
        $this->rolesAdministradorIdsOriginales = $datosMatriz['roles_administrador_ids'];
        $this->permisosIdsOriginales = $datosMatriz['permisos_ids'];
        $this->asignacionesOriginales = $datosMatriz['asignaciones'];
        $this->matriz = [];

        foreach ($this->rolesEditablesIdsOriginales as $roleId) {
            foreach ($this->permisosIdsOriginales as $permisoId) {
                $this->matriz[$roleId][$permisoId] = in_array(
                    $permisoId,
                    $this->asignacionesOriginales[$roleId] ?? [],
                    true
                );
            }
        }

        $this->guardado = false;
    }

    public function guardar(): void
    {
        $this->autorizar('editar');
        $this->guardado = false;

        if (! $this->matrizTieneEstructuraValida()) {
            $this->cargarMatriz();
            session()->flash('error', 'La matriz enviada contiene datos no válidos. Se recargó la información y no se guardaron cambios.');

            return;
        }

        try {
            $resultado = DB::transaction(function (): array {
                $roles = Role::query()
                    ->where('activo', true)
                    ->orderBy('id_rol')
                    ->lockForUpdate()
                    ->get();
                $permisosIds = Permiso::query()
                    ->orderBy('id_permiso')
                    ->lockForUpdate()
                    ->pluck('id_permiso')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                $rolesIds = $roles->pluck('id_rol')->map(static fn ($id): int => (int) $id)->all();
                $rolesAdministradorIds = $roles
                    ->filter(fn (Role $role): bool => $this->esRolAdministrador($role))
                    ->pluck('id_rol')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
                $rolesEditablesIds = $roles
                    ->reject(fn (Role $role): bool => $this->esRolAdministrador($role))
                    ->pluck('id_rol')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                if (
                    $rolesIds !== $this->rolesIdsOriginales
                    || $rolesEditablesIds !== $this->rolesEditablesIdsOriginales
                    || $rolesAdministradorIds !== $this->rolesAdministradorIdsOriginales
                    || $permisosIds !== $this->permisosIdsOriginales
                ) {
                    return ['estado' => 'conflicto'];
                }

                $asignacionesActuales = $this->obtenerAsignaciones($rolesIds);

                foreach ($rolesEditablesIds as $roleId) {
                    if (($asignacionesActuales[$roleId] ?? []) !== ($this->asignacionesOriginales[$roleId] ?? null)) {
                        return ['estado' => 'conflicto'];
                    }
                }

                foreach ($rolesEditablesIds as $roleId) {
                    $role = $roles->firstWhere('id_rol', $roleId);

                    if (! $role || $this->esRolAdministrador($role)) {
                        return ['estado' => 'conflicto'];
                    }

                    $permisosSeleccionados = [];

                    foreach ($this->matriz[$roleId] as $permisoId => $seleccionado) {
                        if ($seleccionado) {
                            $permisosSeleccionados[] = (int) $permisoId;
                        }
                    }

                    sort($permisosSeleccionados, SORT_NUMERIC);
                    $role->permisos()->sync($permisosSeleccionados);
                    $asignacionesActuales[$roleId] = $permisosSeleccionados;
                }

                return [
                    'estado' => 'guardado',
                    'asignaciones' => $asignacionesActuales,
                ];
            });
        } catch (Throwable $exception) {
            report($exception);
            session()->flash('error', 'No fue posible guardar los permisos. No se aplicaron cambios; actualiza la matriz e inténtalo nuevamente.');

            return;
        }

        if ($resultado['estado'] === 'conflicto') {
            $this->cargarMatriz();
            session()->flash('error', 'Los permisos o el catálogo cambiaron desde que abriste la matriz. Se actualizó la información; revisa los valores e intenta nuevamente.');

            return;
        }

        $this->asignacionesOriginales = $resultado['asignaciones'];
        $this->guardado = true;

        session()->flash('success', 'Permisos guardados correctamente en la base de datos.');
    }

    public function render(): View
    {
        $this->autorizar('ver');

        return view('admin.permisos.matriz');
    }

    private function autorizar(string $accion): void
    {
        abort_unless(auth()->user()?->esAdministrador() ?? false, 403);
        abort_unless(Gate::allows('Usuarios y Roles.'.$accion), 403);
    }

    private function esRolAdministrador(Role $role): bool
    {
        return mb_strtolower(trim($role->nombre)) === 'administrador';
    }

    private function matrizTieneEstructuraValida(): bool
    {
        if (! $this->idsCoinciden(array_keys($this->matriz), $this->rolesEditablesIdsOriginales)) {
            return false;
        }

        foreach ($this->matriz as $permisosDict) {
            if (! is_array($permisosDict) || ! $this->idsCoinciden(array_keys($permisosDict), $this->permisosIdsOriginales)) {
                return false;
            }

            foreach ($permisosDict as $seleccionado) {
                if (! is_bool($seleccionado)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<int, int|string>  $idsRecibidos
     * @param  array<int>  $idsEsperados
     */
    private function idsCoinciden(array $idsRecibidos, array $idsEsperados): bool
    {
        $idsRecibidos = array_map(static fn (int|string $id): string => (string) $id, $idsRecibidos);
        $idsEsperados = array_map(static fn (int $id): string => (string) $id, $idsEsperados);
        sort($idsRecibidos, SORT_STRING);
        sort($idsEsperados, SORT_STRING);

        return $idsRecibidos === $idsEsperados;
    }

    /**
     * @param  array<int>  $rolesIds
     * @return array<int, array<int>>
     */
    private function obtenerAsignaciones(array $rolesIds): array
    {
        $asignaciones = array_fill_keys($rolesIds, []);

        if ($rolesIds === []) {
            return $asignaciones;
        }

        $filasAsignacion = DB::table('rol_permiso')
            ->whereIn('FK_id_rol', $rolesIds)
            ->orderBy('FK_id_rol')
            ->orderBy('FK_id_permiso')
            ->lockForUpdate()
            ->get(['FK_id_rol', 'FK_id_permiso']);

        foreach ($filasAsignacion as $fila) {
            $asignaciones[(int) $fila->FK_id_rol][] = (int) $fila->FK_id_permiso;
        }

        return $asignaciones;
    }
}
