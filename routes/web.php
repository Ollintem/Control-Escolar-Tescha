<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PermisoController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas de la Matriz de Permisos
Route::get('/admin/permisos', [PermisoController::class, 'index'])->name('permisos.index');
Route::post('/admin/permisos', [PermisoController::class, 'update'])->name('permisos.update');