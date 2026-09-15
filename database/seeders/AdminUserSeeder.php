<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Crear usuario admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@tescha.edu.mx'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin12345'), // Cambia esta contraseña si gustas
                'activo' => true,
            ]
        );

        // Si usas Spatie Permissions, le asignamos el rol 'admin'
        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }
    }
}