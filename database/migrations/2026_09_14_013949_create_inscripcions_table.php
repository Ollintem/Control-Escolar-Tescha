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
        Schema::create('inscripciones', function (Blueprint $table) {
            $table->id('id_inscripcion'); // PK personalizada

            // FKs alineadas con las tablas 'alumnos' y 'grupos'
            $table->unsignedBigInteger('id_alumno');
            $table->unsignedBigInteger('id_grupo');

            $table->date('fecha_inscripcion')->nullable();
            $table->string('estatus', 20)->default('ordinario'); // ordinario, repeticion, especial
            $table->decimal('calificacion_final', 4, 1)->nullable();
            $table->timestamps();

            // Relaciones de llave foránea corregidas
            $table->foreign('id_alumno')
                  ->references('id_alumno')
                  ->on('alumnos')
                  ->onDelete('cascade');

            $table->foreign('id_grupo')
                  ->references('id_grupo')
                  ->on('grupos')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inscripciones');
    }
};