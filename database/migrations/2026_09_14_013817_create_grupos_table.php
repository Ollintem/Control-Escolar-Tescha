<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_id')->constrained('materias')->onDelete('cascade');
            $table->foreignId('docente_id')->constrained('docentes')->onDelete('cascade');
            $table->foreignId('periodo_escolar_id')->constrained('periodos_escolares')->onDelete('cascade');
            $table->string('clave_grupo', 10); // Ej: 351-V, A, B
            $table->integer('capacidad_maxima')->default(30);
            $table->string('aula', 20)->nullable(); // Ej: CC-1, Aula 12
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
