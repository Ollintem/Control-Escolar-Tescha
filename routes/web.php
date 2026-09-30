<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermisoController;

Route::redirect('/', '/login');

// Rutas protegidas por autenticación.
// La validación de cuenta activa la aplica el middleware global 'activo'
// (registrado en bootstrap/app.php sobre el grupo web).
Route::middleware(['auth'])->group(function () {
    // Panel del Administrador: solo el rol Administrador.
    Route::view('dashboard', 'admin.dashboard.index')
        ->middleware('rol:Administrador')
        ->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    // ===== MÓDULO DE PERMISOS (diseño original) =====
    Route::middleware('rol:Administrador')->group(function () {
        Route::get('/admin/permisos', [PermisoController::class, 'index'])->name('permisos.index');
        Route::post('/admin/permisos', [PermisoController::class, 'update'])->name('permisos.update');
    });
});

require __DIR__.'/auth.php';