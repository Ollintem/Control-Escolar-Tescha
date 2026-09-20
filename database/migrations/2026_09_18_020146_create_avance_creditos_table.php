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
        Schema::create('avance_creditos', function (Blueprint $table) {
            $table->id('id_avance');

            // FKs con tipo unsignedBigInteger para coincidir con alumnos y periodo_escolars
            $table->unsignedBigInteger('id_alumno');
            $table->unsignedBigInteger('id_periodo')->nullable();

            $table->smallInteger('creditos_acumulados')->default(0);
            $table->tinyInteger('creditos_periodo')->default(0);
            $table->decimal('promedio_periodo', 5, 2)->nullable();
            $table->decimal('promedio_general', 5, 2)->nullable();
            $table->tinyInteger('materias_acreditadas')->default(0);
            $table->tinyInteger('materias_reprobadas')->default(0);
            
            // Reemplaza la columna manual por timestamps() de Laravel (gestiona created_at y updated_at)
            $table->timestamps();

            // Claves foráneas
            $table->foreign('id_alumno')
                  ->references('id_alumno')
                  ->on('alumnos')
                  ->onDelete('cascade');

            $table->foreign('id_periodo')
                  ->references('id_periodo')
                  ->on('periodo_escolars')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('avance_creditos');
    }
};