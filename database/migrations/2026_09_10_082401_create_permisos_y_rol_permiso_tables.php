<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabla de catálogo de permisos/módulos
        Schema::create('permisos', function (Blueprint $table) {
            $table->smallIncrements('id_permiso');
            $table->string('modulo', 60); // Ej: Docentes, Calificaciones, Inscripciones
            $table->string('accion', 60); // Ej: ver, crear, editar, eliminar
            $table->string('descripcion', 120)->nullable();
            $table->timestamps();
        });

        // Tabla pivote que conecta los Roles con los Permisos (las casillas activadas)
        Schema::create('rol_permiso', function (Blueprint $table) {
            $table->unsignedTinyInteger('FK_id_rol');
            $table->unsignedSmallInteger('FK_id_permiso');

            $table->foreign('FK_id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
            $table->foreign('FK_id_permiso')->references('id_permiso')->on('permisos')->onDelete('cascade');

            $table->primary(['FK_id_rol', 'FK_id_permiso']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rol_permiso');
        Schema::dropIfExists('permisos');
    }
};