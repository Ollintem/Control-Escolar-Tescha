<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Jefe de Carrera pasa a ser un registro independiente de Docente.
 *
 * - Agrega las columnas de datos personales a jefes_carrera.
 * - Copia los datos del docente enlazado a los registros existentes (backfill).
 * - Elimina la columna id_docente y su clave foránea.
 *
 * Nota: la migración original 2026_09_17_232652_create_jefes_carrera_table
 * NO se modifica porque ya fue ejecutada.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1) Agregar columnas de datos personales (primero nullable para poder rellenar)
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->string('nombre', 50)->nullable()->after('id_jefe_carrera');
            $table->string('apellido_paterno', 50)->nullable()->after('nombre');
            $table->string('apellido_materno', 50)->nullable()->after('apellido_paterno');
            $table->string('no_empleado', 20)->nullable()->after('apellido_materno');
            $table->string('email', 100)->nullable()->after('no_empleado');
        });

        // 2) Backfill: copiar los datos desde el docente enlazado (si existe)
        //    Se hace fila por fila para que sea seguro con cualquier volumen de datos.
        $jefes = DB::table('jefes_carrera')->whereNotNull('id_docente')->get();
        foreach ($jefes as $jefe) {
            $docente = DB::table('docentes')->where('id_docente', $jefe->id_docente)->first();

            if (!$docente) {
                continue; // no debería ocurrir: la FK garantiza que el docente existe
            }

            DB::table('jefes_carrera')->where('id_jefe_carrera', $jefe->id_jefe_carrera)->update([
                'nombre'           => $docente->nombre,
                'apellido_paterno' => $docente->apellido_paterno,
                'apellido_materno' => $docente->apellido_materno,
                'no_empleado'      => $docente->no_empleado,
                'email'            => $docente->email,
            ]);
        }

        // 3) Campos requeridos pasan a ser obligatorios en BD
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->string('nombre', 50)->nullable(false)->change();
            $table->string('apellido_paterno', 50)->nullable(false)->change();
            $table->string('no_empleado', 20)->nullable(false)->change();
            $table->string('email', 100)->nullable(false)->change();
            $table->date('fecha_inicio')->nullable(false)->change();
        });

        // 4) Unicidad dentro de jefes_carrera (independiente de docentes)
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->unique('no_empleado', 'jefes_carrera_no_empleado_unique');
            $table->unique('email', 'jefes_carrera_email_unique');
        });

        // 5) Eliminar la dependencia con docentes: primero la FK, luego la columna
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->dropForeign(['id_docente']);
        });

        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->dropColumn('id_docente');
        });
    }

    public function down(): void
    {
        // Re-agregar la columna como nullable
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->unsignedBigInteger('id_docente')->nullable()->after('id_carrera');
        });

        // Intentar re-vincular por No. de empleado cuando la persona aún exista como docente
        $jefes = DB::table('jefes_carrera')->get();
        foreach ($jefes as $jefe) {
            $docente = DB::table('docentes')->where('no_empleado', $jefe->no_empleado)->first();

            if ($docente) {
                DB::table('jefes_carrera')
                    ->where('id_jefe_carrera', $jefe->id_jefe_carrera)
                    ->update(['id_docente' => $docente->id_docente]);
            }
        }

        // Restaurar la clave foránea
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->foreign('id_docente')
                  ->references('id_docente')
                  ->on('docentes')
                  ->onDelete('cascade');
        });

        // Quitar columnas y unicidades agregadas por esta migración
        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->dropUnique('jefes_carrera_no_empleado_unique');
            $table->dropUnique('jefes_carrera_email_unique');
        });

        Schema::table('jefes_carrera', function (Blueprint $table) {
            $table->dropColumn(['nombre', 'apellido_paterno', 'apellido_materno', 'no_empleado', 'email']);
        });
    }
};
