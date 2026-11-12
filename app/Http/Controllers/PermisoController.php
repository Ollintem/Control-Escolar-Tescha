<?php

namespace App\Http\Controllers;

use App\Models\Permiso;
use App\Models\Role;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use JsonException;
use Throwable;

class PermisoController extends Controller
{
    /**
     * Mostrar la matriz de permisos por rol.
     */
    public function index(Request $request): View
    {
        $this->autorizarMatriz('ver');

        $roles = Role::query()
            ->where('activo', true)
            ->orderBy('id_rol')
            ->with('permisos')
            ->get();
        $roleId = $request->query('role_id');

        if ($roleId === null || $roleId === '') {
            $roleId = $roles->first()?->id_rol;
        } else {
            $validated = $request->validate([
                'role_id' => ['required', 'integer', Rule::exists('roles', 'id_rol')->where('activo', true)],
            ]);

            $roleId = (int) $validated['role_id'];
        }

        $roleSeleccionado = $roleId === null
            ? null
            : $roles->firstWhere('id_rol', (int) $roleId);
        $permisosCatalogo = Permiso::query()->orderBy('id_permiso')->get();
        $permisos = $permisosCatalogo->groupBy('modulo');
        $esAdministrador = $roleSeleccionado !== null && $this->esRolAdministrador($roleSeleccionado);
        $permisosExistentes = $esAdministrador
            ? $permisosCatalogo->pluck('id_permiso')->map(static fn ($id): int => (int) $id)->all()
            : ($roleSeleccionado?->permisos->pluck('id_permiso')->map(static fn ($id): int => (int) $id)->all() ?? []);
        $permisosExistentes = $this->ordenarIds($permisosExistentes);
        $matrixVersion = $roleSeleccionado === null
            ? null
            : $this->crearVersionMatriz((int) $roleSeleccionado->id_rol, $roleSeleccionado->permisos->pluck('id_permiso')->map(static fn ($id): int => (int) $id)->all());

        return view('admin.permisos.index', compact(
            'roles',
            'roleSeleccionado',
            'permisos',
            'permisosExistentes',
            'matrixVersion',
            'esAdministrador'
        ));
    }

    /**
     * Guardar los permisos de un rol.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->autorizarMatriz('editar');

        $validated = $request->validate([
            'role_id' => ['required', 'integer', Rule::exists('roles', 'id_rol')->where('activo', true)],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['required', 'integer', 'distinct', Rule::exists('permisos', 'id_permiso')],
            'matrix_version' => ['required', 'string', 'max:4096'],
        ]);

        $roleId = (int) $validated['role_id'];

        try {
            $versionOriginal = json_decode(
                Crypt::decryptString($validated['matrix_version']),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (DecryptException|JsonException) {
            return $this->redireccionarConError($roleId, 'La versión de la matriz no es válida. Actualiza la información antes de guardar.');
        }

        if (
            ! is_array($versionOriginal)
            || ! isset($versionOriginal['role_id'], $versionOriginal['permisos'])
            || (int) $versionOriginal['role_id'] !== $roleId
            || ! is_array($versionOriginal['permisos'])
            || ! $this->idsNumericosValidos($versionOriginal['permisos'])
        ) {
            return $this->redireccionarConError($roleId, 'La versión de la matriz no coincide con el rol seleccionado. Actualiza la información antes de guardar.');
        }

        $permisosOriginales = $this->ordenarIds(array_map('intval', $versionOriginal['permisos']));
        $permisosSeleccionados = $this->ordenarIds(array_map('intval', $validated['permisos'] ?? []));

        try {
            $resultado = DB::transaction(function () use ($roleId, $permisosOriginales, $permisosSeleccionados): string {
                $role = Role::query()
                    ->where('activo', true)
                    ->lockForUpdate()
                    ->findOrFail($roleId);

                if ($this->esRolAdministrador($role)) {
                    return 'administrador';
                }

                $permisosActualesExistentes = $permisosSeleccionados === []
                    ? []
                    : Permiso::query()
                        ->whereIn('id_permiso', $permisosSeleccionados)
                        ->orderBy('id_permiso')
                        ->lockForUpdate()
                        ->pluck('id_permiso')
                        ->map(static fn ($id): int => (int) $id)
                        ->all();

                if ($permisosActualesExistentes !== $permisosSeleccionados) {
                    return 'permisos_invalidos';
                }

                $asignacionesActuales = DB::table('rol_permiso')
                    ->where('FK_id_rol', $roleId)
                    ->orderBy('FK_id_permiso')
                    ->lockForUpdate()
                    ->pluck('FK_id_permiso')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();

                if ($asignacionesActuales !== $permisosOriginales) {
                    return 'conflicto';
                }

                $role->permisos()->sync($permisosSeleccionados);

                return 'actualizado';
            });
        } catch (ModelNotFoundException) {
            return redirect()
                ->route('permisos.index')
                ->with('error', 'El rol ya no está activo o disponible. Actualiza la matriz e inténtalo nuevamente.');
        } catch (Throwable $exception) {
            report($exception);

            return $this->redireccionarConError($roleId, 'No fue posible guardar los permisos. No se aplicaron cambios; actualiza la matriz e inténtalo nuevamente.');
        }

        if ($resultado === 'administrador') {
            return $this->redireccionarConError($roleId, 'Las asignaciones del Administrador no se pueden modificar.');
        }

        if ($resultado === 'permisos_invalidos') {
            return $this->redireccionarConError($roleId, 'Uno o más permisos ya no están disponibles. No se guardaron cambios.');
        }

        if ($resultado === 'conflicto') {
            return $this->redireccionarConError($roleId, 'Los permisos de este rol cambiaron desde que abriste la matriz. Se actualizó la información; revisa los valores e intenta nuevamente.');
        }

        return redirect()
            ->route('permisos.index', ['role_id' => $roleId])
            ->with('success', 'Permisos actualizados correctamente.');
    }

    private function autorizarMatriz(string $accion): void
    {
        abort_unless(auth()->user()?->esAdministrador() ?? false, 403);
        Gate::authorize('Usuarios y Roles.'.$accion);
    }

    private function esRolAdministrador(Role $role): bool
    {
        return mb_strtolower(trim($role->nombre)) === 'administrador';
    }

    private function crearVersionMatriz(int $roleId, array $permisosIds): string
    {
        $permisosIds = $this->ordenarIds($permisosIds);

        return Crypt::encryptString(json_encode([
            'role_id' => $roleId,
            'permisos' => $permisosIds,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @param  array<int, mixed>  $ids
     */
    private function idsNumericosValidos(array $ids): bool
    {
        foreach ($ids as $id) {
            if (! is_int($id) && (! is_string($id) || ! ctype_digit($id))) {
                return false;
            }
        }

        return count($ids) === count(array_unique(array_map('intval', $ids)));
    }

    /**
     * @param  array<int, int|string>  $ids
     * @return array<int, int>
     */
    private function ordenarIds(array $ids): array
    {
        $ids = array_map(static fn (int|string $id): int => (int) $id, $ids);
        sort($ids, SORT_NUMERIC);

        return $ids;
    }

    private function redireccionarConError(int $roleId, string $mensaje): RedirectResponse
    {
        $parametros = Role::query()
            ->whereKey($roleId)
            ->where('activo', true)
            ->exists()
                ? ['role_id' => $roleId]
                : [];

        return redirect()
            ->route('permisos.index', $parametros)
            ->with('error', $mensaje);
    }
}
