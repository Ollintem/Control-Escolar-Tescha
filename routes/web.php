<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\MatrizPermisos;

Route::redirect('/', '/login');

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::view('profile', 'profile')->name('profile');
    
    // Ruta del módulo de permisos
    Route::get('/permisos', MatrizPermisos::class)->name('permisos.index');
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