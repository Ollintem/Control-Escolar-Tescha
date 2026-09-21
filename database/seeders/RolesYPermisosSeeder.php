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

        // ===== PERMISOS (11 módulos × 4 acciones = 44 permisos) =====
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('rol_permiso')->truncate();
        DB::table('permisos')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $modulos = [
            'Usuarios y Roles',
            'Carreras y Semestres',
            'Materias y Plan de Estudios',
            'Periodos Escolares',
            'Alumnos',
            'Docentes',
            'Jefes de Carrera',
            'Grupos y Asignaciones',
            'Inscripciones',
            'Calificaciones y Actas',
            'Historial Académico',
        ];

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];
        $id = 1;

        foreach ($modulos as $modulo) {
            foreach ($acciones as $accion) {
                Permiso::create([
                    'id_permiso'  => $id,
                    'modulo'      => $modulo,
                    'accion'      => $accion,
                    'descripcion' => ucfirst($accion) . ' ' . $modulo,
                ]);
                $id++;
            }
        }

        // ===== RELACIÓN ROL-PERMISO (Matriz de acceso) =====

        // Administrador: TODOS los permisos (1-44)
        $this->syncPermisos(1, range(1, 44));

        // Control Escolar: todo excepto eliminar en Usuarios y Roles
        // Ver (1) Crear (2) Editar (3) de Usuarios y Roles + todo lo demás menos eliminar (8)
        $permisosCE = [];
        for ($i = 2; $i <= 44; $i++) {
            // Saltar eliminar de Usuarios y Roles (id=4)
            if ($i == 4) continue;
            $permisosCE[] = $i;
        }
        $this->syncPermisos(2, $permisosCE);

        // Jefe de Carrera: ver todo, crear/editar en módulos académicos
        $permisosJC = [];
        for ($i = 1; $i <= 44; $i++) {
            $accion = (($i - 1) % 4); // 0=ver, 1=crear, 2=editar, 3=eliminar
            if ($accion == 0) { $permisosJC[] = $i; } // ver todo
            if ($accion == 1 || $accion == 2) { $permisosJC[] = $i; } // crear y editar
        }
        $this->syncPermisos(3, $permisosJC);

        // Docente: ver en casi todos, crear/editar solo en Calificaciones y Actas
        $permisosDoc = [];
        for ($i = 1; $i <= 44; $i++) {
            $accion = (($i - 1) % 4);
            if ($accion == 0) { $permisosDoc[] = $i; } // ver todo
            // Crear y editar solo en Calificaciones y Actas (módulo 10, permisos 37-40)
            if ($i >= 37 && $i <= 38) { $permisosDoc[] = $i; }
        }
        $this->syncPermisos(4, $permisosDoc);

        // Alumno: solo ver
        $permisosAlumno = [];
        for ($i = 1; $i <= 44; $i++) {
            $accion = (($i - 1) % 4);
            if ($accion == 0) { $permisosAlumno[] = $i; }
        }
        $this->syncPermisos(5, $permisosAlumno);
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
