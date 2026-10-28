<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\JefeCarrera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class PanelJefeCarreraAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const RUTAS_PANEL = [
        'panel.jefe-carrera.index',
        'panel.jefe-carrera.materias-plan',
        'panel.jefe-carrera.docentes',
        'panel.jefe-carrera.grupos',
        'panel.jefe-carrera.alumnos',
        'panel.jefe-carrera.calificaciones',
        'panel.jefe-carrera.historial',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'control_escolar_test') {
            throw new RuntimeException('PanelJefeCarreraAccessTest solo puede ejecutarse en la base aislada control_escolar_test.');
        }
    }

    public function test_jefe_with_active_profile_and_career_can_open_every_panel_route(): void
    {
        $carrera = $this->crearCarrera('TST-PANEL-ACTIVA', true);
        $jefe = $this->crearJefe($carrera, 'JC-PANEL-ACTIVO-01', true);
        $usuario = $this->crearUsuarioJefe($jefe, 'jefe.panel.activo@example.test', true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))->assertOk();
        }
    }

    public function test_inactive_jefe_profile_blocks_every_panel_route_without_exposing_career_data(): void
    {
        $carrera = $this->crearCarrera('TST-PANEL-PERFIL-INACTIVO', true);
        $jefe = $this->crearJefe($carrera, 'JC-PANEL-INACTIVO-01', false);
        $usuario = $this->crearUsuarioJefe($jefe, 'jefe.panel.inactivo@example.test', true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))
                ->assertStatus(403)
                ->assertSee('El perfil de Jefe de Carrera está inactivo.')
                ->assertDontSee($carrera->nombre);
        }

        $this->assertDatabaseHas('users', [
            'id' => $usuario->id,
            'activo' => 1,
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
        ]);
    }

    public function test_inactive_career_blocks_every_panel_route_without_exposing_career_data(): void
    {
        $carrera = $this->crearCarrera('TST-PANEL-CARRERA-INACTIVA', false);
        $jefe = $this->crearJefe($carrera, 'JC-PANEL-CARRERA-INACTIVA', true);
        $usuario = $this->crearUsuarioJefe($jefe, 'jefe.panel.carrera-inactiva@example.test', true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))
                ->assertStatus(403)
                ->assertSee('La carrera asignada está inactiva.')
                ->assertDontSee($carrera->nombre);
        }
    }

    public function test_account_without_a_valid_jefe_profile_is_restricted_from_every_panel_route(): void
    {
        $usuario = $this->crearUsuarioJefe(null, 'jefe.panel.sin-perfil@example.test', true);

        $this->actingAs($usuario);

        foreach (self::RUTAS_PANEL as $ruta) {
            $this->get(route($ruta))
                ->assertStatus(403)
                ->assertSee('La cuenta no tiene un perfil de Jefe de Carrera vinculado.');
        }
    }

    public function test_access_is_rechecked_after_profile_or_career_is_reactivated(): void
    {
        $carrera = $this->crearCarrera('TST-PANEL-REACTIVACION', true);
        $jefe = $this->crearJefe($carrera, 'JC-PANEL-REACTIVAR-01', false);
        $usuario = $this->crearUsuarioJefe($jefe, 'jefe.panel.reactivar@example.test', true);

        $this->actingAs($usuario);

        $this->get(route('panel.jefe-carrera.index'))->assertStatus(403);

        $jefe->activo = 1;
        $jefe->save();

        $this->get(route('panel.jefe-carrera.index'))->assertOk();

        $carrera->activo = 0;
        $carrera->save();

        $this->get(route('panel.jefe-carrera.index'))->assertStatus(403);

        $carrera->activo = 1;
        $carrera->save();

        $this->get(route('panel.jefe-carrera.index'))->assertOk();
    }

    public function test_inactive_user_account_remains_blocked_by_the_existing_account_middleware(): void
    {
        $carrera = $this->crearCarrera('TST-PANEL-CUENTA-INACTIVA', true);
        $jefe = $this->crearJefe($carrera, 'JC-PANEL-CUENTA-INACTIVA', true);
        $usuario = $this->crearUsuarioJefe($jefe, 'jefe.panel.cuenta-inactiva@example.test', false);

        $this->actingAs($usuario)
            ->get(route('panel.jefe-carrera.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_with_a_different_role_cannot_open_jefe_panel_even_with_a_profile_link(): void
    {
        $carrera = $this->crearCarrera('TST-PANEL-ROL-DISTINTO', true);
        $jefe = $this->crearJefe($carrera, 'JC-PANEL-ROL-DISTINTO', true);
        $usuario = User::create([
            'name' => 'Docente de prueba',
            'email' => 'docente.panel.rol@example.test',
            'password' => 'contrasena-de-prueba',
            'rol' => 'docente',
            'FK_id_rol' => Role::where('nombre', 'Docente')->value('id_rol'),
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'activo' => 1,
        ]);

        $this->actingAs($usuario)
            ->get(route('panel.jefe-carrera.index'))
            ->assertStatus(403);
    }

    private function crearCarrera(string $clave, bool $activa): Carrera
    {
        return Carrera::create([
            'clave' => $clave,
            'nombre' => 'Carrera '.$clave,
            'reticula_oficial' => null,
            'activo' => $activa,
        ]);
    }

    private function crearJefe(Carrera $carrera, string $numeroEmpleado, bool $activo): JefeCarrera
    {
        return JefeCarrera::create([
            'id_carrera' => $carrera->id_carrera,
            'nombre' => 'Jefe '.$numeroEmpleado,
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'no_empleado' => $numeroEmpleado,
            'email' => strtolower($numeroEmpleado).'@example.test',
            'fecha_inicio' => '2026-01-01',
            'activo' => $activo,
        ]);
    }

    private function crearUsuarioJefe(?JefeCarrera $jefe, string $correo, bool $activo): User
    {
        return User::create([
            'name' => 'Usuario Jefe de prueba',
            'email' => $correo,
            'password' => 'contrasena-de-prueba',
            'rol' => 'docente',
            'FK_id_rol' => Role::where('nombre', 'Jefe de Carrera')->value('id_rol'),
            'id_jefe_carrera' => $jefe?->id_jefe_carrera,
            'activo' => $activo,
        ]);
    }
}
