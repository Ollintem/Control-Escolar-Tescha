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
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_usuario'); // PK INT AI
            $table->string('nombre', 60);
            $table->string('apellido_paterno', 40)->nullable();
            $table->string('apellido_materno', 40)->nullable();
            $table->string('email', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Llave foránea conectada a la tabla roles
            $table->unsignedTinyInteger('FK_id_rol')->default(1);
            $table->foreign('FK_id_rol')->references('id_rol')->on('roles')->onDelete('cascade');
            
            $table->dateTime('ultimo_acceso')->nullable();
            $table->unsignedTinyInteger('activo')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
