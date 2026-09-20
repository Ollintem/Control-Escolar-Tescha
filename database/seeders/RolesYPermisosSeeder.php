<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permiso;
use Illuminate\Support\Facades\DB;

class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // ===== ROLES =====
        $rolesData = [
            ['id_rol' => 1, 'nombre' => 'Administrador',   'descripcion' => 'Administración general del sistema',  'activo' => 1],
            ['id_rol' => 2, 'nombre' => 'Control Escolar', 'descripcion' => 'Gestión de procesos escolares',     'activo' => 1],
            ['id_rol' => 3, 'nombre' => 'Jefe de Carrera', 'descripcion' => 'Gestión académica de su carrera',   'activo' => 1],
            ['id_rol' => 4, 'nombre' => 'Docente',         'descripcion' => 'Consulta y captura académica',      'activo' => 1],
            ['id_rol' => 5, 'nombre' => 'Alumno',          'descripcion' => 'Consulta de información académica', 'activo' => 1],
        ];

        foreach ($rolesData as $rol) {
            Role::updateOrCreate(
                ['id_rol' => $rol['id_rol']],
                $rol
            );
        }

        // ===== PERMISOS =====
        $permisosData = [
            // Usuarios y roles
            ['id_permiso' => 1,  'modulo' => 'Usuarios', 'accion' => 'ver',     'descripcion' => 'Ver usuarios'],
            ['id_permiso' => 2,  'modulo' => 'Usuarios', 'accion' => 'crear',   'descripcion' => 'Crear usuarios'],
            ['id_permiso' => 3,  'modulo' => 'Usuarios', 'accion' => 'editar',  'descripcion' => 'Editar usuarios'],
            ['id_permiso' => 4,  'modulo' => 'Usuarios', 'accion' => 'eliminar','descripcion' => 'Eliminar usuarios'],

            // Roles
            ['id_permiso' => 5,  'modulo' => 'Roles',    'accion' => 'ver',     'descripcion' => 'Ver roles'],
            ['id_permiso' => 6,  'modulo' => 'Roles',    'accion' => 'crear',   'descripcion' => 'Crear roles'],
            ['id_permiso' => 7,  'modulo' => 'Roles',    'accion' => 'editar',  'descripcion' => 'Editar roles y permisos'],
            ['id_permiso' => 8,  'modulo' => 'Roles',    'accion' => 'eliminar','descripcion' => 'Eliminar roles'],

            // Académico
            ['id_permiso' => 9,  'modulo' => 'Académico','accion' => 'ver',     'descripcion' => 'Ver información académica'],
            ['id_permiso' => 10, 'modulo' => 'Académico','accion' => 'crear',   'descripcion' => 'Crear registros académicos'],
            ['id_permiso' => 11, 'modulo' => 'Académico','accion' => 'editar',  'descripcion' => 'Editar registros académicos'],

            // Calificaciones
            ['id_permiso' => 12, 'modulo' => 'Calificaciones','accion' => 'ver',     'descripcion' => 'Ver calificaciones'],
            ['id_permiso' => 13, 'modulo' => 'Calificaciones','accion' => 'crear',   'descripcion' => 'Capturar calificaciones'],
            ['id_permiso' => 14, 'modulo' => 'Calificaciones','accion' => 'editar',  'descripcion' => 'Editar calificaciones'],
            ['id_permiso' => 15, 'modulo' => 'Calificaciones','accion' => 'eliminar','descripcion' => 'Eliminar calificaciones'],

            // Reportes
            ['id_permiso' => 16, 'modulo' => 'Reportes', 'accion' => 'ver',     'descripcion' => 'Ver reportes'],
            ['id_permiso' => 17, 'modulo' => 'Reportes', 'accion' => 'crear',   'descripcion' => 'Generar reportes'],

            // Configuración
            ['id_permiso' => 18, 'modulo' => 'Configuración','accion' => 'ver',     'descripcion' => 'Ver configuración'],
            ['id_permiso' => 19, 'modulo' => 'Configuración','accion' => 'editar',  'descripcion' => 'Editar configuración'],
        ];

        foreach ($permisosData as $permiso) {
            Permiso::updateOrCreate(
                ['id_permiso' => $permiso['id_permiso']],
                $permiso
            );
        }

        // ===== RELACIÓN ROL-PERMISO (Matriz de acceso) =====
        DB::table('rol_permiso')->truncate();

        // Administrador: TODOS los permisos (1-19)
        $adminPermisos = range(1, 19);
        $this->syncPermisos(1, $adminPermisos);

        // Control Escolar: todo excepto eliminar usuarios/roles
        $this->syncPermisos(2, [1,2,3,5,6,7,9,10,11,12,13,14,16,17,18,19]);

        // Jefe de Carrera: ver, académico, calificaciones, reportes
        $this->syncPermisos(3, [1,5,9,10,11,12,13,14,16,17]);

        // Docente: ver y calificaciones
        $this->syncPermisos(4, [1,5,9,12,13,16]);

        // Alumno: solo ver
        $this->syncPermisos(5, [1,5,9,12,16]);
    }

    private function syncPermisos(int $roleId, array $permisoIds): void
    {
        foreach ($permisoIds as $permisoId) {
            DB::table('rol_permiso')->insertOrIgnore([
                'FK_id_rol'     => $roleId,
                'FK_id_permiso' => $permisoId,
            ]);
        }
    }
}
