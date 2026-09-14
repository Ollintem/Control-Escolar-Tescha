<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('cascade');
            $table->string('clave', 20)->unique(); // Ej: SCD-1015
            $table->string('nombre');               // Ej: Estructura de Datos
            $table->integer('creditos');            // Ej: 5
            $table->integer('horas_teoricas');      // Ej: 2
            $table->integer('horas_practicas');     // Ej: 3
            $table->integer('semestre_sugerido');   // Ej: 3
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
