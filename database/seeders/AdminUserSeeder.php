<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@tescha.edu.mx'],
            [
                'name' => 'Administrador General',
                'password' => Hash::make('admin12345'),
                'rol' => 'admin',
                'activo' => true,
            ]
        );

        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }
    }
}