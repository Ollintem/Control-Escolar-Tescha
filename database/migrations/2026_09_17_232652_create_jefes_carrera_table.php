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
        Schema::create('jefes_carrera', function (Blueprint $table) {
            $table->id('id_jefe_carrera');
            
            // FKs con tipo unsignedBigInteger alineadas con id_carrera e id_docente
            $table->unsignedBigInteger('id_carrera');
            $table->unsignedBigInteger('id_docente');
            
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Definición de restricciones de llave foránea
            $table->foreign('id_carrera')
                  ->references('id_carrera')
                  ->on('carreras')
                  ->onDelete('cascade');

            $table->foreign('id_docente')
                  ->references('id_docente')
                  ->on('docentes')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jefes_carrera');
    }
};