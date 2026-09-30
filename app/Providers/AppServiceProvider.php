<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Autorización centralizada derivada de la Matriz de Permisos existente
     * (roles -> rol_permiso -> permisos), sin 44 Gate::define manuales.
     *
     * Formato de habilidad: "{Módulo}.{acción}" tal como existen en la tabla
     * permisos, p. ej. Gate::allows('Docentes.editar') o @can('Docentes.crear').
     *
     * - El rol "Administrador" (reconocido por NOMBRE de rol, sin IDs fijos)
     *   conserva acceso total a cualquier habilidad.
     * - Los demás roles se resuelven consultando la relación role -> permisos.
     * - Las habilidades que no sigan el formato quedan denegadas por defecto
     *   (solo pasarían si se declaran explícitamente en el futuro).
     */
    public function boot(): void
    {
        Gate::before(function (User $user, string $ability) {
            if ($user->esAdministrador()) {
                return true;
            }

            [$modulo, $accion] = array_pad(explode('.', $ability, 2), 2, null);

            if ($modulo === null || $accion === null || $modulo === '' || $accion === '') {
                return null;
            }

            return $user->tienePermiso($modulo, $accion);
        });
    }
}
