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
        Schema::create('inscripcion_grupos', function (Blueprint $table) {
            $table->id('id_inscripcion_grupo');
            
            // Llaves foráneas con tipo unsignedBigInteger
            $table->unsignedBigInteger('id_inscripcion');
            $table->unsignedBigInteger('id_grupo');
            
            $table->timestamps();

            // Apunta a 'inscripciones' (en plural español) en lugar de 'inscripcions'
            $table->foreign('id_inscripcion')
                  ->references('id_inscripcion')
                  ->on('inscripciones')
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
        Schema::dropIfExists('inscripcion_grupos');
    }
};