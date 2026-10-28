<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo "estado" a los periodos escolares.
 *
 * Valores permitidos: 'Próximo' | 'Activo' | 'Finalizado'.
 *
 * La columna existente "activo" (tinyint) NO se elimina ni se altera: se
 * mantiene sincronizada con "estado" (activo = 1 solo cuando estado = 'Activo')
 * para conservar sin cambios las consultas de los paneles de Alumno, Docente,
 * Jefe de Carrera y Control Escolar, que usan where('activo', 1).
 *
 * Esta migración solo AGREGA una columna y actualiza el estado del periodo ya
 * existente. No elimina ni modifica ningún otro dato.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periodo_escolars', function (Blueprint $table) {
            $table->string('estado', 20)->default('Finalizado')->after('activo');
        });

        // Backfill: los periodos hoy marcados como activos pasan a 'Activo'.
        // Los demás conservan el valor por defecto 'Finalizado'.
        DB::table('periodo_escolars')->where('activo', 1)->update(['estado' => 'Activo']);
    }

    public function down(): void
    {
        Schema::table('periodo_escolars', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
