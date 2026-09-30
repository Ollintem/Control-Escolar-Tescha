<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Fuente oficial del rol: tabla roles, localizado POR NOMBRE
        // (nunca se asume un ID fijo ni se usa el ENUM legacy users.rol).
        $rolAdministrador = Role::where('nombre', 'Administrador')->value('id_rol');

        if ($rolAdministrador === null) {
            throw new RuntimeException(
                'No existe el rol "Administrador" en la tabla roles. '
                . 'Ejecuta RolesYPermisosSeeder primero (DatabaseSeeder ya los ordena así).'
            );
        }

        User::firstOrCreate(
            ['email' => 'admin@tescha.edu.mx'],
            [
                'name'     => 'Administrador General',
                'password' => Hash::make('admin12345'),
                'FK_id_rol' => $rolAdministrador,
                'activo'   => true,
                // La columna ENUM legacy users.rol se OMITE a propósito:
                // aplica su DEFAULT del esquema y no se usa para autorización
                // (fuente oficial: FK_id_rol -> roles.id_rol).
                // No se usa assignRole() (no existe en esta arquitectura).
            ]
        );
    }
}
