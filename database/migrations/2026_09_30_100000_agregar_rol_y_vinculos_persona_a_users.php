<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Etapa 1 (autorizada): conectar users con roles y preparar el vinculo
     * con la persona academica correspondiente, sin modificar docentes,
     * jefes_carrera ni alumnos.
     *
     * - FK_id_rol: NOT NULL, todo usuario pertenece a UN unico rol (FK -> roles.id_rol).
     * - id_docente / id_jefe_carrera / id_alumno: nullable + UNIQUE + FK a su tabla
     *   ("1 cuenta = 1 persona" para los roles Docente, Jefe de Carrera y Alumno).
     *   La correspondencia rol <-> tipo de persona NO se valida aqui (etapa posterior
     *   del modulo Usuarios).
     * - Todas las FK usan ON DELETE RESTRICT (decision del equipo).
     * - La columna ENUM legacy users.rol NO se elimina en esta etapa.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('FK_id_rol')->nullable()->after('rol');
            $table->unsignedBigInteger('id_docente')->nullable()->after('FK_id_rol');
            $table->unsignedBigInteger('id_jefe_carrera')->nullable()->after('id_docente');
            $table->unsignedBigInteger('id_alumno')->nullable()->after('id_jefe_carrera');

            $table->foreign('FK_id_rol')
                ->references('id_rol')->on('roles')
                ->onDelete('restrict');

            $table->foreign('id_docente')
                ->references('id_docente')->on('docentes')
                ->onDelete('restrict');

            $table->foreign('id_jefe_carrera')
                ->references('id_jefe_carrera')->on('jefes_carrera')
                ->onDelete('restrict');

            $table->foreign('id_alumno')
                ->references('id_alumno')->on('alumnos')
                ->onDelete('restrict');

            $table->unique('id_docente', 'users_id_docente_unique');
            $table->unique('id_jefe_carrera', 'users_id_jefe_carrera_unique');
            $table->unique('id_alumno', 'users_id_alumno_unique');
        });

        // Backfill minimo autorizado: el usuario existente con rol legacy 'admin'
        // apunta al ID REAL del rol "Administrador" en la tabla roles (no se asume 1).
        $idRolAdministrador = DB::table('roles')
            ->where('nombre', 'Administrador')
            ->value('id_rol');

        if ($idRolAdministrador === null) {
            throw new RuntimeException('No existe el rol "Administrador" en la tabla roles; backfill abortado.');
        }

        DB::table('users')
            ->where('rol', 'admin')
            ->whereNull('FK_id_rol')
            ->update(['FK_id_rol' => $idRolAdministrador]);

        $usuariosSinRol = DB::table('users')->whereNull('FK_id_rol')->count();

        if ($usuariosSinRol > 0) {
            throw new RuntimeException(
                "{$usuariosSinRol} usuario(s) quedaron sin FK_id_rol tras el backfill; " .
                'asignales un rol antes de reintentar la migracion.'
            );
        }

        // Recien despues del backfill, FK_id_rol pasa a ser obligatorio.
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('FK_id_rol')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_fk_id_rol_foreign');
            $table->dropForeign('users_id_docente_foreign');
            $table->dropForeign('users_id_jefe_carrera_foreign');
            $table->dropForeign('users_id_alumno_foreign');
            $table->dropUnique('users_id_docente_unique');
            $table->dropUnique('users_id_jefe_carrera_unique');
            $table->dropUnique('users_id_alumno_unique');
            $table->dropColumn(['FK_id_rol', 'id_docente', 'id_jefe_carrera', 'id_alumno']);
        });
    }
};
