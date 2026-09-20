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
        Schema::create('asignaciones_docente', function (Blueprint $table) {
            $table->id('id_asignacion');
            
            // Llaves foráneas con tipo unsignedBigInteger alineadas a sus tablas
            $table->unsignedBigInteger('id_docente');
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_grupo')->nullable();
            
            $table->string('horas_asignadas', 10)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Restricciones de llave foránea
            $table->foreign('id_docente')
                  ->references('id_docente')
                  ->on('docentes')
                  ->onDelete('cascade');

            $table->foreign('id_materia')
                  ->references('id_materia')
                  ->on('materias')
                  ->onDelete('cascade');

            $table->foreign('id_grupo')
                  ->references('id_grupo')
                  ->on('grupos')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignaciones_docente');
    }
};