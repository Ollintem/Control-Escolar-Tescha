<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodo_escolars', function (Blueprint $table) {
            $table->id('id_periodo'); // PK personalizada para alinearse con 'grupos'
            $table->string('clave', 20)->unique(); // Ej: 2026-1
            $table->string('nombre', 50); // Ej: Febrero - Junio 2026
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodo_escolars');
    }
};