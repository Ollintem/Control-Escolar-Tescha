<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RolController extends Controller
{
    /**
     * Listar todos los roles con conteo de usuarios y permisos.
     */
    public function index()
    {
        $roles = Role::withCount('permisos')->get();

        return response()->json($roles);
    }

    /**
     * Guardar un nuevo rol.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'     => 'required|string|min:2|max:30|unique:roles,nombre',
            'descripcion' => 'nullable|string|max:120',
        ]);

        $role = Role::create([
            'nombre'     => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? null,
            'activo'     => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rol creado correctamente.',
            'role'    => $role,
        ], 201);
    }

    /**
     * Actualizar un rol existente.
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'nombre'     => 'required|string|min:2|max:30|unique:roles,nombre,' . $role->id_rol . ',id_rol',
            'descripcion' => 'nullable|string|max:120',
            'activo'     => 'nullable|boolean',
        ]);

        $role->update([
            'nombre'     => $validated['nombre'],
            'descripcion' => $validated['descripcion'] ?? $role->descripcion,
            'activo'     => $request->boolean('activo', $role->activo) ? 1 : 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado correctamente.',
            'role'    => $role,
        ]);
    }

    /**
     * Eliminar un rol.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);

        // No eliminar el rol Administrador (id_rol = 1)
        if ($role->id_rol == 1) {
            return response()->json([
                'success' => false,
                'message' => 'El rol Administrador no se puede eliminar.',
            ], 403);
        }

        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente.',
        ]);
    }
}
