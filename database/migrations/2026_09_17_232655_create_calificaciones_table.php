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
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id('id_calificacion');

            // FKs con tipo unsignedBigInteger
            $table->unsignedBigInteger('id_inscripcion');
            $table->unsignedBigInteger('id_capturada_por')->nullable(); // Debe ser nullable para permitir 'set null'

            $table->tinyInteger('parcial')->nullable(); // Ej: 1, 2, 3 o evaluación continua
            $table->decimal('calificacion', 4, 1)->nullable();
            $table->string('observaciones', 255)->nullable();
            $table->timestamps();

            // Definición de llaves foráneas
            $table->foreign('id_inscripcion')
                  ->references('id_inscripcion')
                  ->on('inscripciones')
                  ->onDelete('cascade');

            $table->foreign('id_capturada_por')
                  ->references('id_docente')
                  ->on('docentes')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};