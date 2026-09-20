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
        Schema::create('semestres', function (Blueprint $table) {
            $table->smallIncrements('id_semestre'); // Llave primaria personalizada
            $table->unsignedBigInteger('id_carrera'); // Coincide exactamente con el id_carrera de la tabla carreras
            $table->tinyInteger('numero'); // Número de semestre (1 al 9)
            $table->string('descripcion', 60)->nullable();

            // Restricción de llave foránea
            $table->foreign('id_carrera')
                  ->references('id_carrera')
                  ->on('carreras')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semestres');
    }
};