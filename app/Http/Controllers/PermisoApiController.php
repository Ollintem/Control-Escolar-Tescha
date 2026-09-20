<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

class PermisoApiController extends Controller
{
    /**
     * Obtener la matriz completa de permisos (roles × permisos).
     */
    public function index()
    {
        $roles = Role::where('activo', true)->get();
        $permisos = Permiso::all();
        $matriz = [];

        foreach ($roles as $role) {
            $asignados = $role->permisos->pluck('id_permiso')->toArray();
            foreach ($permisos as $permiso) {
                $matriz[$role->id_rol][$permiso->id_permiso] = in_array($permiso->id_permiso, $asignados);
            }
        }

        return response()->json([
            'roles'    => $roles,
            'permisos' => $permisos,
            'matriz'   => $matriz,
        ]);
    }

    /**
     * Guardar permisos para todos los roles (recibe la matriz completa).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'matriz'                => 'required|array',
            'matriz.*'              => 'array',
            'matriz.*.*'            => 'boolean',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['matriz'] as $idRol => $permisosDict) {
                $role = Role::find($idRol);

                if ($role) {
                    $permisosSeleccionados = [];

                    foreach ($permisosDict as $idPermiso => $marcado) {
                        if ($marcado) {
                            $permisosSeleccionados[] = $idPermiso;
                        }
                    }

                    $role->permisos()->sync($permisosSeleccionados);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Permisos guardados correctamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar permisos: ' . $e->getMessage(),
            ], 500);
        }
    }
}
