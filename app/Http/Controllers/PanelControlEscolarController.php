<?php

namespace App\Http\Controllers;

use App\Models\PeriodoEscolar;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Panel de Control Escolar (misma arquitectura que Alumno, Docente y Jefe de
 * Carrera).
 *
 * Reglas:
 *  - Cada página exige su permiso de la Matriz de Permisos EN EL SERVIDOR con
 *    Gate ("{Módulo}.{accion}"), además de lo que muestre la vista: la
 *    seguridad no depende de ocultar botones.
 *  - NO se expone "Materias y Plan de Estudios" (Control Escolar tiene 0
 *    permisos sobre ese módulo) ni ninguna ruta de la Matriz de Permisos,
 *    Roles ni Configuración: son exclusivas del Administrador.
 *  - Inscripciones, Calificaciones y Actas e Historial Académico aún no tienen
 *    interfaz: sus páginas muestran un estado informativo, sin datos inventados.
 *  - Gestión de Usuarios se muestra en modo consulta (lectura real desde BD);
 *    no se crean rutas de escritura en esta etapa.
 */
class PanelControlEscolarController extends Controller
{
    /**
     * Inicio: contadores REALES de los módulos que el rol puede consultar.
     * (No se cuentan materias ni planes: ese módulo no le corresponde.)
     */
    public function index()
    {
        return view('panels.control-escolar.index', [
            'periodoActivo' => $this->periodoActivo(),
            'totalAlumnos' => DB::table('alumnos')->count(),
            'totalInscripciones' => DB::table('inscripciones')->count(),
            'totalGrupos' => DB::table('grupos')->count(),
            'totalCalificaciones' => DB::table('calificaciones')->count(),
            'totalDocentes' => DB::table('docentes')->count(),
            'totalUsuarios' => DB::table('users')->count(),
        ]);
    }

    /** Gestión Escolar -> Alumnos (reutiliza el componente Livewire existente). */
    public function alumnos()
    {
        $this->exigir('Alumnos');

        return view('panels.control-escolar.alumnos');
    }

    /** Gestión Escolar -> Inscripciones (módulo aún sin interfaz). */
    public function inscripciones()
    {
        $this->exigir('Inscripciones');

        return view('panels.control-escolar.inscripciones');
    }

    /** Gestión Escolar -> Grupos y Asignaciones (componente Livewire existente). */
    public function grupos()
    {
        $this->exigir('Grupos y Asignaciones');

        return view('panels.control-escolar.grupos');
    }

    /** Gestión Escolar -> Calificaciones y Actas (módulo aún sin interfaz). */
    public function calificaciones()
    {
        $this->exigir('Calificaciones y Actas');

        return view('panels.control-escolar.calificaciones');
    }

    /** Gestión Escolar -> Historial Académico (módulo aún sin interfaz). */
    public function historial()
    {
        $this->exigir('Historial Académico');

        return view('panels.control-escolar.historial');
    }

    /** Catálogos -> Docentes (componente Livewire existente). */
    public function docentes()
    {
        $this->exigir('Docentes');

        return view('panels.control-escolar.docentes');
    }

    /** Catálogos -> Jefes de Carrera (componente Livewire existente). */
    public function jefesCarrera()
    {
        $this->exigir('Jefes de Carrera');

        return view('panels.control-escolar.jefes-carrera');
    }

    /** Catálogos -> Carreras (componente Livewire existente). */
    public function carreras()
    {
        $this->exigir('Carreras y Semestres');

        return view('panels.control-escolar.carreras');
    }

    /**
     * Catálogos -> Semestres / Periodos (componente Livewire existente).
     * La página administra ambas cosas, así que exige ver en LOS DOS módulos
     * de la Matriz: "Carreras y Semestres" (semestres) y "Periodos Escolares".
     */
    public function semestresPeriodos()
    {
        $this->exigir('Carreras y Semestres');
        $this->exigir('Periodos Escolares');

        return view('panels.control-escolar.semestres-periodos');
    }

    /**
     * Usuarios -> Gestión de Usuarios (modo consulta).
     *
     * La Matriz otorga a Control Escolar "Usuarios y Roles": ver, crear y
     * editar (NO eliminar). Esta página solo LEE cuentas reales; no expone
     * ningún endpoint de escritura, de modo que una petición manual de
     * eliminación no tiene por dónde ejecutarse desde este panel.
     */
    public function gestionUsuarios()
    {
        $this->exigir('Usuarios y Roles');

        return view('panels.control-escolar.gestion-usuarios', [
            'usuarios' => DB::table('users')
                ->leftJoin('roles', 'roles.id_rol', '=', 'users.FK_id_rol')
                ->orderBy('users.name')
                ->get(['users.id', 'users.name', 'users.email', 'users.activo', 'roles.nombre as rol']),
        ]);
    }

    /**
     * Autorización EN EL SERVIDOR según la Matriz de Permisos
     * (Gate "{Modulo}.{accion}"). Un usuario sin "ver" recibe 403 aunque
     * conozca la URL: la protección no depende de los botones de la vista.
     */
    private function exigir(string $modulo): void
    {
        abort_unless(
            Gate::allows($modulo . '.ver'),
            403,
            'No tienes permiso para acceder a esta sección.'
        );
    }

    /** Solo el periodo con activo = 1; si no existe, no se muestra nada. */
    private function periodoActivo(): ?PeriodoEscolar
    {
        return PeriodoEscolar::where('activo', 1)->orderBy('id_periodo')->first();
    }
}
