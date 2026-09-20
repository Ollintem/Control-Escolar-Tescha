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
        Schema::create('grupos', function (Blueprint $table) {
            $table->id('id_grupo'); // PK personalizada
            
            // FKs ajustadas a la nomenclatura en español y tipo unsignedBigInteger
            $table->unsignedBigInteger('id_materia');
            $table->unsignedBigInteger('id_docente')->nullable();
            $table->unsignedBigInteger('id_periodo');
            
            $table->string('nombre', 20); // Ej: 501, 502, 901
            $table->smallInteger('cupo_maximo')->default(30);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Claves foráneas hacia sus respectivas tablas
            $table->foreign('id_materia')
                  ->references('id_materia')
                  ->on('materias')
                  ->onDelete('cascade');

            $table->foreign('id_docente')
                  ->references('id_docente')
                  ->on('docentes')
                  ->onDelete('set null');

            $table->foreign('id_periodo')
                  ->references('id_periodo')
                  ->on('periodo_escolars')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};