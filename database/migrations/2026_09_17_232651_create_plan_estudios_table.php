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
        Schema::create('plan_estudios', function (Blueprint $table) {
            $table->id('id_plan_estudio'); // PK personalizada
            
            // FK ajustada como unsignedBigInteger para coincidir exactamente con id_carrera en carreras
            $table->unsignedBigInteger('id_carrera'); 
            
            $table->string('clave', 30)->unique(); // Ej: ISIC-2010-224
            $table->string('nombre', 100);
            $table->year('anio_publicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Definición de la llave foránea
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
        Schema::dropIfExists('plan_estudios');
    }
};