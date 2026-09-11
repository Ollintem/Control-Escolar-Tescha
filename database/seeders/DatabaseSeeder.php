<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
{
    \DB::table('roles')->insert([
        [
            'id_rol' => 1,
            'nombre' => 'Administrador',
            'descripcion' => 'Control total del sistema',
            'activo' => 1
        ],
        [
            'id_rol' => 2,
            'nombre' => 'Usuario',
            'descripcion' => 'Acceso estándar',
            'activo' => 1
        ],
    ]);
}
}
