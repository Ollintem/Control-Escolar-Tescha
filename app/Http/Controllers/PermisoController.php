<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permiso;

class PermisoController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::all();
        $roleId = $request->get('role_id', $roles->first()?->id_rol);
        
        $roleSeleccionado = Role::with('permisos')->find($roleId);
        $permisosExistentes = $roleSeleccionado ? $roleSeleccionado->permisos->pluck('id_permiso')->toArray() : [];

        // Agrupar permisos por módulo
        $permisos = Permiso::all()->groupBy('modulo');

        return view('admin.permisos.index', compact('roles', 'roleSeleccionado', 'permisos', 'permisosExistentes'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id_rol',
            'permisos' => 'nullable|array',
            'permisos.*' => 'exists:permisos,id_permiso',
        ]);

        $role = Role::findOrFail($request->role_id);
        $role->permisos()->sync($request->input('permisos', []));

        return redirect()->back()->with('success', 'Permisos actualizados correctamente.');
    }
}