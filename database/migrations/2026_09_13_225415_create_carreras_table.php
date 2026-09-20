<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id('id_carrera'); // Se especifica 'id_carrera' para que coincida con la FK de semestres
            $table->string('clave', 20)->unique(); // Ej: ISC-2010-224
            $table->string('nombre');              // Ej: Ingeniería en Sistemas Computacionales
            $table->string('reticula_oficial')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};