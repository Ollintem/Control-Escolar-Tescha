<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Orden correcto: los roles/permisos deben existir ANTES de crear
            // el Administrador (users.FK_id_rol es NOT NULL y apunta a roles).
            RolesYPermisosSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
