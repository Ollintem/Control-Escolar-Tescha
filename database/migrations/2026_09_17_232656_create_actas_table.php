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
        Schema::create('actas', function (Blueprint $table) {
            $table->id('id_acta');

            // FKs alineadas con tipo unsignedBigInteger
            $table->unsignedBigInteger('id_grupo');
            $table->unsignedBigInteger('id_docente')->nullable(); // Nullable para permitir set null

            $table->string('folio', 50)->nullable()->unique();
            $table->string('tipo_evaluacion', 30)->default('ordinario'); // ordinario, extraordinario, especial
            $table->date('fecha_cierre')->nullable();
            $table->boolean('cerrada')->default(false);
            $table->timestamps();

            // Definición de restricciones de llave foránea
            $table->foreign('id_grupo')
                  ->references('id_grupo')
                  ->on('grupos')
                  ->onDelete('cascade');

            $table->foreign('id_docente')
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
        Schema::dropIfExists('actas');
    }
};