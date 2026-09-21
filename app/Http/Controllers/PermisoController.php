<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permiso;

class PermisoController extends Controller
{
    /**
     * Mostrar la matriz de permisos por rol.
     */
    public function index(Request $request)
    {
        $roles = Role::where('activo', true)->get();
        $roleId = $request->get('role_id', $roles->first()?->id_rol);
        $roleSeleccionado = Role::with('permisos')->find($roleId);
        $permisosExistentes = $roleSeleccionado
            ? $roleSeleccionado->permisos->pluck('id_permiso')->toArray()
            : [];

        // Agrupar permisos por módulo, conservando el orden
        $permisos = Permiso::all()->groupBy('modulo');

        return view('admin.permisos.index', compact(
            'roles',
            'roleSeleccionado',
            'permisos',
            'permisosExistentes'
        ));
    }

    /**
     * Guardar los permisos de un rol.
     */
    public function update(Request $request)
    {
        $roleId = $request->input('role_id');

        // Proteger el rol Administrador (id_rol = 1)
        if ($roleId == 1) {
            return redirect()
                ->route('permisos.index', ['role_id' => 1])
                ->with('error', 'Los permisos del Administrador no se pueden modificar. Es el rol principal del sistema.');
        }

        $request->validate([
            'role_id'    => 'required|exists:roles,id_rol',
            'permisos'   => 'nullable|array',
            'permisos.*' => 'exists:permisos,id_permiso',
        ]);

        $role = Role::findOrFail($roleId);
        $role->permisos()->sync($request->input('permisos', []));

        return redirect()
            ->route('permisos.index', ['role_id' => $roleId])
            ->with('success', 'Permisos actualizados correctamente.');
    }
}
