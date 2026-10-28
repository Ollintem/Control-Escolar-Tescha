<?php

namespace Tests\Feature;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PanelAlumnoAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const RUTAS_PANEL = [
        'panel.alumno.index',
        'panel.alumno.calificaciones',
        'panel.alumno.historial',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'control_escolar_test') {
            throw new RuntimeException('PanelAlumnoAccessTest solo puede ejecutarse en la base aislada control_escolar_test.');
        }
    }

    public function test_active_alumno_with_active_account_can_access_every_academic_route(): void
    {
        $alumno = $this->crearAlumno(true);
        $usuario = $this->crearUsuario(Role::where('nombre', 'Alumno')->firstOrFail(), $alumno, true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    public function test_inactive_alumno_profile_blocks_every_academic_route_without_changing_account_or_records(): void
    {
        $alumno = $this->crearAlumno(false);
        $usuario = $this->crearUsuario(Role::where('nombre', 'Alumno')->firstOrFail(), $alumno, true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))
                ->assertForbidden()
                ->assertViewIs('panels.alumno.acceso-restringido')
                ->assertSee('El perfil académico está inactivo.')
                ->assertDontSee('Mis Calificaciones')
                ->assertDontSee('Mi Historial Académico');
        }

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'activo' => 1,
            'id_alumno' => $alumno->id_alumno,
        ]);
        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'activo' => 0,
        ]);
    }

    public function test_account_without_a_valid_alumno_profile_is_restricted_from_every_academic_route(): void
    {
        $usuario = $this->crearUsuario(Role::where('nombre', 'Alumno')->firstOrFail(), null, true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))
                ->assertForbidden()
                ->assertViewIs('panels.alumno.acceso-restringido')
                ->assertSee('La cuenta no tiene un perfil de alumno vinculado.');
        }
    }

    public function test_reactivated_alumno_profile_restores_access_on_a_later_request(): void
    {
        $alumno = $this->crearAlumno(false);
        $usuario = $this->crearUsuario(Role::where('nombre', 'Alumno')->firstOrFail(), $alumno, true);

        $this->actingAs($usuario)
            ->get(route('panel.alumno.index'))
            ->assertForbidden();

        $alumno->activo = true;
        $alumno->save();

        $this->get(route('panel.alumno.index'))->assertOk();
    }

    public function test_inactive_user_account_remains_blocked_by_the_existing_account_middleware(): void
    {
        $alumno = $this->crearAlumno(true);
        $usuario = $this->crearUsuario(Role::where('nombre', 'Alumno')->firstOrFail(), $alumno, false);

        $this->actingAs($usuario)
            ->get(route('panel.alumno.index'))
            ->assertRedirect(route('login'));
    }

    public function test_other_role_can_access_its_panel_but_not_the_alumno_panel(): void
    {
        $usuarioDocente = $this->crearUsuario(Role::where('nombre', 'Docente')->firstOrFail(), null, true);

        $this->actingAs($usuarioDocente)
            ->get(route('panel.docente.index'))
            ->assertOk();

        $this->get(route('panel.alumno.index'))->assertForbidden();
    }

    private function crearAlumno(bool $activo): Alumno
    {
        $carrera = Carrera::create([
            'clave' => 'TST-PANEL-ALU',
            'nombre' => 'Carrera de prueba del panel alumno',
            'reticula_oficial' => null,
            'activo' => 1,
        ]);

        return Alumno::create([
            'id_carrera' => $carrera->id_carrera,
            'no_control' => 'TST-PANEL-ALU-01',
            'nombre' => 'Alumno de prueba',
            'apellido_paterno' => 'TESCHA',
            'apellido_materno' => null,
            'email' => 'alumno.panel@example.test',
            'activo' => $activo,
        ]);
    }

    private function crearUsuario(Role $rol, ?Alumno $alumno, bool $activo): User
    {
        return User::create([
            'name' => 'Usuario de prueba del panel alumno',
            'email' => 'usuario.panel.alumno@example.test',
            'password' => 'contrasena-de-prueba',
            'rol' => mb_strtolower($rol->nombre),
            'FK_id_rol' => $rol->id_rol,
            'id_alumno' => $alumno?->id_alumno,
            'activo' => $activo,
        ]);
    }
}
