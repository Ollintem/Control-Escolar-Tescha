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
        Schema::create('alumnos', function (Blueprint $table) {
            $table->id('id_alumno');
            $table->unsignedBigInteger('id_carrera'); 
            
            $table->string('no_control', 20)->unique();
            $table->string('nombre', 50);
            $table->string('apellido_paterno', 50);
            $table->string('apellido_materno', 50)->nullable();
            $table->string('email', 100)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Relación foránea corregida hacia 'id_carrera'
            $table->foreign('id_carrera')
                  ->references('id_carrera')
                  ->on('carreras')
                  ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};