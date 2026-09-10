<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Lista de módulos basada en tu diagrama de BDD
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
            'Historial Académico'
        ];

        $acciones = ['ver', 'crear', 'editar', 'eliminar'];

        $permisos = [];

        foreach ($modulos as $modulo) {
            foreach ($acciones as $accion) {
                $permisos[] = [
                    'modulo' => $modulo,
                    'accion' => $accion,
                    'descripcion' => ucfirst($accion) . ' ' . strtolower($modulo),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('permisos')->insert($permisos);
    }
}