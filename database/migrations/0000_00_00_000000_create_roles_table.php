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
    Schema::create('roles', function (Blueprint $table) {
        $table->tinyIncrements('id_rol');
        $table->string('nombre', 30);
        $table->string('descripcion', 120)->nullable();
        $table->unsignedTinyInteger('activo')->default(1);
    });
}
};
