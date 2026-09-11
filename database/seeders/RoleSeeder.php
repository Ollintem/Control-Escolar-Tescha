<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'Docente', 'descripcion' => 'Gestión de asignaturas y calificaciones'],
            ['nombre' => 'Alumno', 'descripcion' => 'Consulta de horarios e historial académico'],
            ['nombre' => 'Jefe de Carrera', 'descripcion' => 'Gestión académica y asignación de grupos'],
            ['nombre' => 'Control Escolar', 'descripcion' => 'Inscripciones y emisión de actas'],
        ];

        foreach ($roles as $rol) {
            Role::firstOrCreate(['nombre' => $rol['nombre']], $rol);
        }
    }
}