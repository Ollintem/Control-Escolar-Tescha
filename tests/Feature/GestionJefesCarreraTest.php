<?php

namespace Tests\Feature;

use App\Livewire\GestionJefesCarrera;
use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\JefeCarrera;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class GestionJefesCarreraTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'control_escolar_test') {
            throw new RuntimeException('GestionJefesCarreraTest solo puede ejecutarse en la base aislada control_escolar_test.');
        }
    }

    public function test_administrator_can_register_and_edit_a_jefe_carrera(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.jefes@example.test'
        ));
        $carrera = $this->crearCarrera();

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalCrear')
            ->set('nombre', 'Jefe de Prueba')
            ->set('apellido_paterno', 'Carrera')
            ->set('apellido_materno', 'TESCHA')
            ->set('no_empleado', 'JC-TEST-01')
            ->set('email', 'jefe.prueba@example.test')
            ->set('id_carrera', $carrera->id_carrera)
            ->set('fecha_inicio', '2026-01-01')
            ->call('guardar')
            ->assertHasNoErrors();

        $jefe = JefeCarrera::where('no_empleado', 'JC-TEST-01')->firstOrFail();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carrera->id_carrera,
            'nombre' => 'Jefe de Prueba',
            'fecha_inicio' => '2026-01-01',
            'activo' => 1,
        ]);
        $this->assertDatabaseMissing('users', ['id_jefe_carrera' => $jefe->id_jefe_carrera]);

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('apellido_paterno', 'Actualizado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'apellido_paterno' => 'Actualizado',
            'id_carrera' => $carrera->id_carrera,
            'activo' => 1,
        ]);
        $this->assertDatabaseMissing('users', ['id_jefe_carrera' => $jefe->id_jefe_carrera]);
    }

    public function test_editing_a_linked_jefe_synchronizes_name_and_email_and_preserves_account_fields(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.sincroniza.jefe@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-SYNC-01', 'jefe.original@example.test');
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            'jefe.original@example.test',
            $jefe
        );
        $passwordHash = $cuenta->password;
        $cuentasDespuesDePreparar = User::count();

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('nombre', 'Nombre Actualizado')
            ->set('apellido_paterno', 'Apellido Actualizado')
            ->set('apellido_materno', 'Materno Actualizado')
            ->set('email', 'jefe.actualizado@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'nombre' => 'Nombre Actualizado',
            'apellido_paterno' => 'Apellido Actualizado',
            'apellido_materno' => 'Materno Actualizado',
            'email' => 'jefe.actualizado@example.test',
            'id_carrera' => $carrera->id_carrera,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'name' => 'Nombre Actualizado Apellido Actualizado Materno Actualizado',
            'email' => 'jefe.actualizado@example.test',
            'FK_id_rol' => $cuenta->FK_id_rol,
            'activo' => 1,
        ]);
        $this->assertSame($passwordHash, User::findOrFail($cuenta->id)->password);
        $this->assertSame($cuentasDespuesDePreparar, User::count());
    }

    public function test_editing_an_unlinked_jefe_does_not_create_a_user(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.sin-cuenta.jefe@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-SIN-CUENTA-01', 'jefe.sin-cuenta@example.test');
        $cuentasAntes = User::count();

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('nombre', 'Perfil Jefe actualizado')
            ->set('email', 'jefe.sin-cuenta.actualizado@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'nombre' => 'Perfil Jefe actualizado',
            'email' => 'jefe.sin-cuenta.actualizado@example.test',
        ]);
        $this->assertSame($cuentasAntes, User::count());
        $this->assertDatabaseMissing('users', ['id_jefe_carrera' => $jefe->id_jefe_carrera]);
    }

    public function test_jefe_can_keep_its_current_account_email_when_only_personal_data_changes(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.email-propio.jefe@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-EMAIL-PROPIO-01', 'jefe.conservar@example.test');
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            'jefe.conservar@example.test',
            $jefe
        );

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('nombre', 'Jefe con correo conservado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => 'Jefe con correo conservado Apellido JC-EMAIL-PROPIO-01',
            'email' => 'jefe.conservar@example.test',
        ]);
    }

    public function test_email_used_by_another_account_is_rejected_without_partial_updates(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.correo-conflicto.jefe@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-CORREO-CONFLICTO-01', 'jefe.correo-original@example.test');
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            'jefe.correo-original@example.test',
            $jefe
        );
        $otraCuenta = $this->crearUsuario(
            Role::where('nombre', 'Docente')->firstOrFail(),
            'correo.otro-usuario@example.test'
        );

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('nombre', 'Este cambio no se persiste')
            ->set('email', $otraCuenta->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('El correo ya pertenece a otra cuenta o persona.');

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'nombre' => 'Jefe JC-CORREO-CONFLICTO-01',
            'email' => 'jefe.correo-original@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'email' => 'jefe.correo-original@example.test',
        ]);
    }

    public function test_same_employee_docente_copy_is_not_merged_or_rejected_as_a_different_person(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.historial-jefe@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'EMP-HIST-JEFE-01', 'empleado.historico.jefe@example.test');
        $docente = Docente::create([
            'no_empleado' => $jefe->no_empleado,
            'nombre' => 'Copia histórica Docente',
            'apellido_paterno' => 'Conservada',
            'apellido_materno' => null,
            'email' => $jefe->email,
            'activo' => 1,
        ]);
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            $jefe->email,
            $jefe
        );

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('nombre', 'Jefe Histórico Actualizado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'nombre' => 'Jefe Histórico Actualizado',
            'no_empleado' => $jefe->no_empleado,
        ]);
        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Copia histórica Docente',
            'email' => 'empleado.historico.jefe@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'name' => 'Jefe Histórico Actualizado Prueba',
            'email' => 'empleado.historico.jefe@example.test',
        ]);
    }

    public function test_email_used_by_another_docente_or_alumno_is_rejected(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.persona-conflicto@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-PERSONA-CONFLICTO-01', 'jefe.persona-original@example.test');
        $docente = Docente::create([
            'no_empleado' => 'DOC-CONFLICTO-01',
            'nombre' => 'Docente diferente',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'correo.docente-otro@example.test',
            'activo' => 1,
        ]);
        $alumno = Alumno::create([
            'id_carrera' => $carrera->id_carrera,
            'no_control' => 'ALU-CONFLICTO-01',
            'nombre' => 'Alumno diferente',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'correo.alumno-otro@example.test',
            'activo' => 1,
        ]);

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('email', $docente->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique']);

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('email', $alumno->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'email' => 'jefe.persona-original@example.test',
        ]);
    }

    public function test_changing_career_waits_for_confirmation_and_cancel_keeps_the_old_career(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.cambio-carrera@example.test'
        ));
        $carreraAnterior = $this->crearCarrera('TST-JC-CARR-ANT');
        $carreraNueva = $this->crearCarrera('TST-JC-CARR-NUEVA');
        $jefe = $this->crearJefe($carreraAnterior, 'JC-CAMBIO-01', 'jefe.cambio@example.test');
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            'jefe.cambio@example.test',
            $jefe
        );

        $componente = Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('nombre', 'Nombre tras cambio')
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirma el cambio de carrera')
            ->assertSee($carreraAnterior->clave)
            ->assertSee($carreraNueva->clave);

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carreraAnterior->id_carrera,
            'nombre' => 'Jefe JC-CAMBIO-01',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => 'Usuario de prueba',
            'email' => 'jefe.cambio@example.test',
        ]);

        $componente
            ->call('cancelarCambioCarrera')
            ->assertHasNoErrors()
            ->assertDontSee('Confirma el cambio de carrera');

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carreraAnterior->id_carrera,
            'nombre' => 'Jefe JC-CAMBIO-01',
        ]);

        $componente
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirma el cambio de carrera')
            ->call('confirmarCambioCarrera')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carreraNueva->id_carrera,
            'nombre' => 'Nombre tras cambio',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => 'Nombre tras cambio Prueba',
            'email' => 'jefe.cambio@example.test',
        ]);
    }

    public function test_inactive_current_career_is_visible_and_can_be_preserved(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.carrera-inactiva@example.test'
        ));
        $carreraInactiva = $this->crearCarrera('TST-JC-CARR-INACTIVA', false);
        $jefe = $this->crearJefe($carreraInactiva, 'JC-CARR-INACTIVA-01', 'jefe.carrera-inactiva@example.test');

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->assertSet('id_carrera', $carreraInactiva->id_carrera)
            ->assertSee('Inactiva · asignada actualmente')
            ->set('nombre', 'Perfil conserva carrera inactiva')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carreraInactiva->id_carrera,
            'nombre' => 'Perfil conserva carrera inactiva',
        ]);
    }

    public function test_inactive_new_career_is_rejected_without_changing_the_profile(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.carrera-nueva-inactiva@example.test'
        ));
        $carreraActual = $this->crearCarrera('TST-JC-CARR-ACTUAL');
        $carreraInactiva = $this->crearCarrera('TST-JC-CARR-NUEVA-INACTIVA', false);
        $jefe = $this->crearJefe($carreraActual, 'JC-CARR-NUEVA-01', 'jefe.carrera-nueva@example.test');

        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('id_carrera', $carreraInactiva->id_carrera)
            ->call('guardar')
            ->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carreraActual->id_carrera,
        ]);
    }

    public function test_career_must_still_be_active_when_the_change_is_confirmed(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.carrera-confirmacion@example.test'
        ));
        $carreraActual = $this->crearCarrera('TST-JC-CONF-ACTUAL');
        $carreraDestino = $this->crearCarrera('TST-JC-CONF-DESTINO');
        $jefe = $this->crearJefe($carreraActual, 'JC-CAMBIO-CONF-01', 'jefe.cambio.confirmacion@example.test');

        $componente = Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefe->id_jefe_carrera)
            ->set('id_carrera', $carreraDestino->id_carrera)
            ->call('guardar')
            ->assertSee('Confirma el cambio de carrera');

        Carrera::whereKey($carreraDestino->id_carrera)->update(['activo' => 0]);

        $componente
            ->call('confirmarCambioCarrera')
            ->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'id_carrera' => $carreraActual->id_carrera,
        ]);
    }

    public function test_administrator_can_deactivate_and_reactivate_without_changing_linked_account(): void
    {
        $this->actingAs($this->crearUsuario(
            Role::where('nombre', 'Administrador')->firstOrFail(),
            'admin.estado.jefe@example.test'
        ));
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-ESTADO-01', 'jefe.estado@example.test');
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            'cuenta.estado.jefe@example.test',
            $jefe
        );
        $passwordHash = $cuenta->password;

        Livewire::test(GestionJefesCarrera::class)
            ->call('desactivar', $jefe->id_jefe_carrera)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'activo' => 0,
            'id_carrera' => $carrera->id_carrera,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'activo' => 1,
            'FK_id_rol' => $cuenta->FK_id_rol,
        ]);
        $this->assertSame($passwordHash, User::findOrFail($cuenta->id)->password);

        Livewire::test(GestionJefesCarrera::class)
            ->call('reactivar', $jefe->id_jefe_carrera)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'activo' => 1,
            'id_carrera' => $carrera->id_carrera,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_jefe_carrera' => $jefe->id_jefe_carrera,
            'activo' => 1,
        ]);
    }

    public function test_control_escolar_can_deactivate_and_reactivate_with_matrix_permissions(): void
    {
        $usuarioControlEscolar = $this->crearUsuario(
            Role::where('nombre', 'Control Escolar')->firstOrFail(),
            'control-escolar.jefes@example.test'
        );
        $carrera = $this->crearCarrera();
        $jefe = $this->crearJefe($carrera, 'JC-ESTADO-02', 'jefe.control@example.test');

        $this->actingAs($usuarioControlEscolar);

        Livewire::test(GestionJefesCarrera::class)
            ->call('desactivar', $jefe->id_jefe_carrera)
            ->assertHasNoErrors();
        $this->assertDatabaseHas('jefes_carrera', ['id_jefe_carrera' => $jefe->id_jefe_carrera, 'activo' => 0]);

        Livewire::test(GestionJefesCarrera::class)
            ->call('reactivar', $jefe->id_jefe_carrera)
            ->assertHasNoErrors();
        $this->assertDatabaseHas('jefes_carrera', ['id_jefe_carrera' => $jefe->id_jefe_carrera, 'activo' => 1]);
    }

    public function test_jefe_carrera_role_cannot_deactivate_but_can_reactivate_by_matrix(): void
    {
        $carrera = $this->crearCarrera();
        $jefeActivo = $this->crearJefe($carrera, 'JC-MATRIZ-01', 'jefe.matriz.a@example.test');
        $jefeInactivo = $this->crearJefe($carrera, 'JC-MATRIZ-02', 'jefe.matriz.b@example.test', false);
        $usuarioJefe = $this->crearUsuario(
            Role::where('nombre', 'Jefe de Carrera')->firstOrFail(),
            'jefe.rol@example.test'
        );

        $this->actingAs($usuarioJefe);

        Livewire::test(GestionJefesCarrera::class)
            ->assertDontSee('Desactivar Jefe de Carrera')
            ->assertSee('Reactivar Jefe de Carrera');
        Livewire::test(GestionJefesCarrera::class)
            ->call('desactivar', $jefeActivo->id_jefe_carrera)
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->call('reactivar', $jefeInactivo->id_jefe_carrera)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('jefes_carrera', ['id_jefe_carrera' => $jefeActivo->id_jefe_carrera, 'activo' => 1]);
        $this->assertDatabaseHas('jefes_carrera', ['id_jefe_carrera' => $jefeInactivo->id_jefe_carrera, 'activo' => 1]);
    }

    public function test_view_only_role_cannot_create_edit_deactivate_or_reactivate_jefes(): void
    {
        $carrera = $this->crearCarrera();
        $jefeActivo = $this->crearJefe($carrera, 'JC-PERMISO-01', 'jefe.permiso.a@example.test');
        $jefeInactivo = $this->crearJefe($carrera, 'JC-PERMISO-02', 'jefe.permiso.b@example.test', false);
        $usuarioSoloConsulta = $this->crearUsuario(
            Role::where('nombre', 'Docente')->firstOrFail(),
            'docente.solo-consulta@example.test'
        );

        $this->actingAs($usuarioSoloConsulta);

        Livewire::test(GestionJefesCarrera::class)
            ->assertDontSee('Nuevo Jefe de Carrera');
        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalCrear')
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->set('nombre', 'Alta no autorizada')
            ->set('apellido_paterno', 'Prueba')
            ->set('no_empleado', 'JC-NO-CREADO')
            ->set('email', 'jefe.no-creado@example.test')
            ->set('id_carrera', $carrera->id_carrera)
            ->set('fecha_inicio', '2026-01-01')
            ->call('guardar')
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->call('abrirModalEditar', $jefeActivo->id_jefe_carrera)
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->call('desactivar', $jefeActivo->id_jefe_carrera)
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->call('reactivar', $jefeInactivo->id_jefe_carrera)
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->call('confirmarCambioCarrera')
            ->assertStatus(403);
        Livewire::test(GestionJefesCarrera::class)
            ->call('cancelarCambioCarrera')
            ->assertStatus(403);

        $this->assertDatabaseHas('jefes_carrera', ['id_jefe_carrera' => $jefeActivo->id_jefe_carrera, 'activo' => 1]);
        $this->assertDatabaseHas('jefes_carrera', ['id_jefe_carrera' => $jefeInactivo->id_jefe_carrera, 'activo' => 0]);
        $this->assertDatabaseMissing('jefes_carrera', ['no_empleado' => 'JC-NO-CREADO']);
    }

    private function crearCarrera(string $clave = 'TST-JC-CARR', bool $activa = true): Carrera
    {
        return Carrera::create([
            'clave' => $clave,
            'nombre' => 'Carrera de prueba '.$clave,
            'reticula_oficial' => null,
            'activo' => $activa,
        ]);
    }

    private function crearJefe(Carrera $carrera, string $numeroEmpleado, string $correo, bool $activo = true): JefeCarrera
    {
        return JefeCarrera::create([
            'id_carrera' => $carrera->id_carrera,
            'nombre' => 'Jefe '.$numeroEmpleado,
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'no_empleado' => $numeroEmpleado,
            'email' => $correo,
            'fecha_inicio' => '2026-01-01',
            'activo' => $activo,
        ]);
    }

    private function crearUsuario(Role $rol, string $correo, ?JefeCarrera $jefe = null): User
    {
        $rolLegacy = match ($rol->nombre) {
            'Administrador' => 'admin',
            'Control Escolar' => 'servicios_escolares',
            default => 'docente',
        };

        return User::create([
            'name' => 'Usuario de prueba',
            'email' => $correo,
            'password' => 'contrasena-de-prueba',
            'rol' => $rolLegacy,
            'FK_id_rol' => $rol->id_rol,
            'id_jefe_carrera' => $jefe?->id_jefe_carrera,
            'activo' => 1,
        ]);
    }
}
