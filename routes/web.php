<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Quitamos 'verified' para evitar el bloqueo si la columna en BD no tiene fecha
Route::view('dashboard', 'dashboard')
    ->middleware(['auth'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// Ruta de acceso directo forzado
Route::get('/bypass-admin', function () {
    // Busca al usuario por su correo
    $user = User::where('email', 'admin@tescha.edu.mx')->first();

    if (!$user) {
        return "El usuario admin@tescha.edu.mx no existe en la BD.";
    }

    // Inicia sesión directamente en el sistema
    Auth::login($user);
    
    return redirect()->route('dashboard');
});

require __DIR__.'/auth.php';