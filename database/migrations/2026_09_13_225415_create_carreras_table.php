<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 20)->unique(); // Ej: ISC-2010-224
            $table->string('nombre');              // Ej: Ingeniería en Sistemas Computacionales
            $table->string('reticula_oficial')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};