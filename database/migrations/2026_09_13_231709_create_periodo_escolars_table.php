<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_escolares', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 15)->unique(); // Ej: 2026-1, 2026-2
            $table->string('nombre');              // Ej: Agosto - Diciembre 2026
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_escolares');
    }
};