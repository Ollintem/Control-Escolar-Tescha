<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->onDelete('cascade');
            $table->foreignId('grupo_id')->constrained('grupos')->onDelete('cascade');
            $table->decimal('calificacion_final', 5, 2)->nullable(); // Ej: 85.50
            $table->enum('tipo_evaluacion', ['ordinario', 'revalidacion', 'extraordinario', 'titulo'])->default('ordinario');
            $table->enum('estatus_materia', ['cursando', 'aprobada', 'reprobada', 'baja'])->default('cursando');
            $table->timestamps();

            // Un alumno no se puede inscribir al mismo grupo dos veces en la misma inscripción
            $table->unique(['inscripcion_id', 'grupo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};