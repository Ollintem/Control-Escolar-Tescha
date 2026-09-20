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
        Schema::create('historial_academico', function (Blueprint $table) {
            $table->id('id_historial');

            // FKs alineadas con tipo unsignedBigInteger
            $table->unsignedBigInteger('id_alumno');
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_periodo')->nullable();

            $table->decimal('calificacion', 4, 1)->nullable();
            $table->string('tipo_acreditacion', 30)->default('ordinario'); // ordinario, extraordinario, especial, revalidación
            $table->boolean('aprobada')->default(true);
            $table->timestamps();

            // Definición de restricciones de llave foránea
            $table->foreign('id_alumno')
                  ->references('id_alumno')
                  ->on('alumnos')
                  ->onDelete('cascade');

            $table->foreign('id_materia')
                  ->references('id_materia')
                  ->on('materias')
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
        Schema::dropIfExists('historial_academico');
    }
};