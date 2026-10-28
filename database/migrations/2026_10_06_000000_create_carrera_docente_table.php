<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Relación MUCHOS A MUCHOS (N:N) entre docentes y carreras.
 *
 * Un docente puede pertenecer a una o varias carreras al mismo tiempo.
 * Un alumno NO: alumnos conserva su columna id_carrera (N:1) sin cambios.
 *
 * Notas de diseño:
 *  - PK compuesta (id_carrera, id_docente): impide a nivel de base de
 *    datos que se asigne la misma carrera dos veces al mismo docente.
 *  - ON DELETE CASCADE en ambos sentidos: si se borra una carrera o un
 *    docente, sus asignaciones se limpian y no quedan filas huérfanas.
 *  - Sin timestamps: misma convención que la tabla pivote ya existente
 *    en el proyecto (rol_permiso).
 *  - La tabla se crea VACÍA: no se deduce la pertenencia a una carrera
 *    a partir de materias ni grupos. Las asignaciones existentes se
 *    actualizan únicamente de forma explícita desde el módulo Docentes
 *    o desde Usuarios y Roles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carrera_docente', function (Blueprint $table) {
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_docente');

            $table->foreign('id_carrera')
                ->references('id_carrera')
                ->on('carreras')
                ->onDelete('cascade');

            $table->foreign('id_docente')
                ->references('id_docente')
                ->on('docentes')
                ->onDelete('cascade');

            // Llave primaria compuesta -> unicidad garantizada por la BD.
            $table->primary(['id_carrera', 'id_docente']);
        });
    }

    public function down(): void
    {
        // Solo elimina la tabla creada por ESTA migración.
        Schema::dropIfExists('carrera_docente');
    }
};
