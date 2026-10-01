<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PanelAlumnoController;
use App\Http\Controllers\PanelDocenteController;
use App\Http\Controllers\PanelJefeCarreraController;
use App\Http\Controllers\PanelControlEscolarController;
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

    // ===== PANEL DEL ALUMNO (piloto por roles) =====
    // Protección doble: sesión iniciada (grupo 'auth') + rol real 'Alumno'
    // resuelto por roles.nombre. Un Administrador aquí recibe 403.
    Route::middleware('rol:Alumno')->prefix('panel/alumno')->name('panel.alumno.')->group(function () {
        Route::get('/', [PanelAlumnoController::class, 'index'])->name('index');
        Route::get('/calificaciones', [PanelAlumnoController::class, 'calificaciones'])->name('calificaciones');
        Route::get('/historial', [PanelAlumnoController::class, 'historial'])->name('historial');
    });

    // ===== PANEL DEL DOCENTE (misma arquitectura que el Panel Alumno) =====
    // Protección doble: sesión iniciada (grupo 'auth') + rol real 'Docente'
    // resuelto por roles.nombre. Alumnos y Administradores reciben 403.
    Route::middleware('rol:Docente')->prefix('panel/docente')->name('panel.docente.')->group(function () {
        Route::get('/', [PanelDocenteController::class, 'index'])->name('index');
        Route::get('/grupos', [PanelDocenteController::class, 'grupos'])->name('grupos');
        Route::get('/alumnos', [PanelDocenteController::class, 'alumnos'])->name('alumnos');
        Route::get('/calificaciones', [PanelDocenteController::class, 'calificaciones'])->name('calificaciones');
    });

    // ===== PANEL DEL JEFE DE CARRERA (misma arquitectura que Alumno/Docente) =====
    // Protección doble: sesión iniciada (grupo 'auth') + rol real 'Jefe de
    // Carrera' resuelto por roles.nombre. Alumnos, Docentes y Administradores
    // reciben 403. Dentro de cada página además se exige el permiso de la
    // Matriz con Gate en el servidor (no depende de esconder botones).
    Route::middleware('rol:Jefe de Carrera')->prefix('panel/jefe-carrera')->name('panel.jefe-carrera.')->group(function () {
        Route::get('/', [PanelJefeCarreraController::class, 'index'])->name('index');
        Route::get('/materias-plan', [PanelJefeCarreraController::class, 'materiasPlan'])->name('materias-plan');
        Route::get('/docentes', [PanelJefeCarreraController::class, 'docentes'])->name('docentes');
        Route::get('/grupos', [PanelJefeCarreraController::class, 'grupos'])->name('grupos');
        Route::get('/alumnos', [PanelJefeCarreraController::class, 'alumnos'])->name('alumnos');
        Route::get('/calificaciones', [PanelJefeCarreraController::class, 'calificaciones'])->name('calificaciones');
        Route::get('/historial', [PanelJefeCarreraController::class, 'historial'])->name('historial');
    });

    // ===== PANEL DE CONTROL ESCOLAR (misma arquitectura que los anteriores) =====
    // Protección doble: sesión iniciada (grupo 'auth') + rol real 'Control
    // Escolar' resuelto por roles.nombre. Alumnos, Docentes, Jefes de Carrera
    // y Administradores reciben 403. Dentro de cada página además se exige el
    // permiso de la Matriz con Gate en el servidor (no depende de botones).
    // NO existe ruta de "Materias y Plan de Estudios" (0 permisos para este
    // rol) ni de Matriz de Permisos / Roles / Configuración.
    Route::middleware('rol:Control Escolar')->prefix('panel/control-escolar')->name('panel.control-escolar.')->group(function () {
        Route::get('/', [PanelControlEscolarController::class, 'index'])->name('index');
        Route::get('/alumnos', [PanelControlEscolarController::class, 'alumnos'])->name('alumnos');
        Route::get('/inscripciones', [PanelControlEscolarController::class, 'inscripciones'])->name('inscripciones');
        Route::get('/grupos', [PanelControlEscolarController::class, 'grupos'])->name('grupos');
        Route::get('/calificaciones', [PanelControlEscolarController::class, 'calificaciones'])->name('calificaciones');
        Route::get('/historial', [PanelControlEscolarController::class, 'historial'])->name('historial');
        Route::get('/docentes', [PanelControlEscolarController::class, 'docentes'])->name('docentes');
        Route::get('/jefes-carrera', [PanelControlEscolarController::class, 'jefesCarrera'])->name('jefes-carrera');
        Route::get('/carreras', [PanelControlEscolarController::class, 'carreras'])->name('carreras');
        Route::get('/semestres-periodos', [PanelControlEscolarController::class, 'semestresPeriodos'])->name('semestres-periodos');
        Route::get('/gestion-usuarios', [PanelControlEscolarController::class, 'gestionUsuarios'])->name('gestion-usuarios');
    });

    // ===== MÓDULO DE PERMISOS (diseño original) =====
    Route::middleware('rol:Administrador')->group(function () {
        Route::get('/admin/permisos', [PermisoController::class, 'index'])->name('permisos.index');
        Route::post('/admin/permisos', [PermisoController::class, 'update'])->name('permisos.update');
    });
});

require __DIR__.'/auth.php';