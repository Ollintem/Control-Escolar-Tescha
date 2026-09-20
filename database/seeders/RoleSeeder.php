<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role; // Importación correcta del modelo Role

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema', 'activo' => 1],
            ['nombre' => 'Control Escolar', 'descripcion' => 'Inscripciones y emisión de actas', 'activo' => 1],
            ['nombre' => 'Docente', 'descripcion' => 'Captura de calificaciones y actas', 'activo' => 1],
            ['nombre' => 'Alumno', 'descripcion' => 'Consulta de calificaciones y horarios', 'activo' => 1],
        ];

        foreach ($roles as $rolData) {
            Role::firstOrCreate(['nombre' => $rolData['nombre']], $rolData);
        }

        // Asignar todos los permisos al Administrador
        $adminRole = Role::where('nombre', 'Administrador')->first();
        // ... tu lógica adicional de permisos si aplica ...
    }
}