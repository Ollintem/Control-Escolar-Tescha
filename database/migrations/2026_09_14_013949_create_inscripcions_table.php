<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumno_id')->constrained('alumnos')->onDelete('cascade');
            $table->foreignId('periodo_escolar_id')->constrained('periodos_escolares')->onDelete('cascade');
            $table->date('fecha_inscripcion');
            $table->enum('estatus', ['activa', 'cancelada', 'finalizada'])->default('activa');
            $table->timestamps();

            // Un alumno solo se inscribe una vez por periodo escolar
            $table->unique(['alumno_id', 'periodo_escolar_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};