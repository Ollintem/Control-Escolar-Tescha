<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('carrera_id')->constrained('carreras')->onDelete('restrict');
            $table->string('numero_control', 15)->unique(); // Ej: 202610001
            $table->string('curp', 18)->unique();
            $table->integer('semestre_actual')->default(1);
            $table->enum('estatus', ['activo', 'baja_temporal', 'baja_definitiva', 'egresado'])->default('activo');
            $table->string('telefono', 15)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
