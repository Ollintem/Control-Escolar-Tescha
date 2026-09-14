<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('rfc', 13)->unique();
            $table->string('curp', 18)->unique();
            $table->string('numero_tarjeta', 20)->unique(); // Matrícula o N° de empleado
            $table->string('titulo_academico')->nullable();  // Ej: Ing., Mtro., Dr.
            $table->string('telefono', 15)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
