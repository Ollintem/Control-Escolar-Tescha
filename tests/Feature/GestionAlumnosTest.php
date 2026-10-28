<?php

namespace Tests\Feature;

use App\Livewire\GestionAlumnos;
use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\PeriodoEscolar;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class GestionAlumnosTest extends TestCase
{
    use DatabaseTransactions;

    private User $administrador;

    private Carrera $carreraActiva;

    private int $secuenciaAlumno = 0;

    private int $secuenciaUsuario = 0;

    private int $secuenciaCarrera = 0;

    private int $secuenciaRegistroAcademico = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'control_escolar_test') {
            throw new RuntimeException('GestionAlumnosTest solo puede ejecutarse en la base aislada control_escolar_test.');
        }

        $rolAdministrador = Role::where('nombre', 'Administrador')->firstOrFail();
        $this->administrador = $this->crearUsuario($rolAdministrador, 'admin');
        $this->carreraActiva = $this->crearCarrera('TST-GEST-BASE', true);
    }

    public function test_administrator_can_create_and_edit_an_alumno_and_changes_persist_after_reload(): void
    {
        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalCrear')
            ->set('id_carrera', $this->carreraActiva->id_carrera)
            ->set('no_control', 'TST-2026-001')
            ->set('nombre', 'Alma de Prueba')
            ->set('apellido_paterno', 'TESCHA')
            ->set('apellido_materno', 'CONTROL')
            ->set('email', 'alumna.001@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $alumno = Alumno::where('no_control', 'TST-2026-001')->firstOrFail();

        $this->assertSame((int) $this->carreraActiva->id_carrera, (int) $alumno->id_carrera);
        $this->assertSame('Alma de Prueba', $alumno->nombre);

        Livewire::test(GestionAlumnos::class)
            ->assertSee('TST-2026-001')
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('nombre', 'Alma Actualizada')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'nombre' => 'Alma Actualizada',
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);

        Livewire::test(GestionAlumnos::class)->assertSee('Alma Actualizada');
    }

    public function test_editing_a_linked_alumno_syncs_personal_data_and_preserves_account_fields(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-LINK-001',
            'nombre' => 'Nombre Original',
            'apellido_paterno' => 'Apellido Original',
            'apellido_materno' => 'Materno Original',
            'email' => 'alumno.linked@example.test',
            'activo' => 0,
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno, false);
        $passwordOriginal = $cuenta->password;
        $rolOriginal = $cuenta->FK_id_rol;

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('nombre', 'Nombre Actualizado')
            ->set('apellido_paterno', 'Apellido Actualizado')
            ->set('apellido_materno', 'Materno Actualizado')
            ->call('guardar')
            ->assertHasNoErrors();

        $alumnoRecargado = Alumno::findOrFail($alumno->id_alumno);
        $cuentaRecargada = User::findOrFail($cuenta->id);

        $this->assertSame('Nombre Actualizado', $alumnoRecargado->nombre);
        $this->assertSame('Apellido Actualizado', $alumnoRecargado->apellido_paterno);
        $this->assertSame('Materno Actualizado', $alumnoRecargado->apellido_materno);
        $this->assertSame('Nombre Actualizado Apellido Actualizado Materno Actualizado', $cuentaRecargada->name);
        $this->assertSame('alumno.linked@example.test', $cuentaRecargada->email);
        $this->assertSame($passwordOriginal, $cuentaRecargada->password);
        $this->assertSame($rolOriginal, $cuentaRecargada->FK_id_rol);
        $this->assertFalse($cuentaRecargada->activo);
        $this->assertFalse((bool) $alumnoRecargado->activo);
        $this->assertSame((int) $this->carreraActiva->id_carrera, (int) $alumnoRecargado->id_carrera);
        $this->assertSame((int) $alumno->id_alumno, (int) $cuentaRecargada->id_alumno);
    }

    public function test_editing_an_unlinked_alumno_updates_only_the_academic_profile(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-UNLINK-001',
            'email' => 'alumno.unlinked@example.test',
        ]);
        $cuentasAntes = User::count();

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('nombre', 'Perfil sin cuenta')
            ->set('email', 'alumno.unlinked.updated@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'nombre' => 'Perfil sin cuenta',
            'email' => 'alumno.unlinked.updated@example.test',
        ]);
        $this->assertDatabaseMissing('users', ['id_alumno' => $alumno->id_alumno]);
        $this->assertSame($cuentasAntes, User::count());
    }

    public function test_linked_alumno_can_keep_its_current_email(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-EMAIL-KEEP',
            'email' => 'alumno.email.keep@example.test',
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('nombre', 'Alumno conserva correo')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'email' => 'alumno.email.keep@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'email' => 'alumno.email.keep@example.test',
        ]);
    }

    public function test_linked_alumno_can_change_to_an_available_email_and_persists_after_reload(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-EMAIL-NEW',
            'email' => 'alumno.email.old@example.test',
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('email', 'alumno.email.new@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $alumnoRecargado = Alumno::findOrFail($alumno->id_alumno);
        $cuentaRecargada = User::findOrFail($cuenta->id);

        $this->assertSame('alumno.email.new@example.test', $alumnoRecargado->email);
        $this->assertSame('alumno.email.new@example.test', $cuentaRecargada->email);
    }

    public function test_email_used_by_another_account_is_rejected_without_partial_updates(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-EMAIL-ACCOUNT',
            'nombre' => 'Nombre Antes',
            'email' => 'alumno.before.account@example.test',
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno);
        $otraCuenta = $this->crearUsuario(Role::where('nombre', 'Docente')->firstOrFail(), 'docente');
        $otraCuenta->email = 'correo.ocupado.cuenta@example.test';
        $otraCuenta->save();

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('nombre', 'Nombre No Persistido')
            ->set('email', $otraCuenta->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'nombre' => 'Nombre Antes',
            'email' => 'alumno.before.account@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => $cuenta->name,
            'email' => 'alumno.before.account@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $otraCuenta->id,
            'email' => 'correo.ocupado.cuenta@example.test',
        ]);
    }

    public function test_email_used_by_another_person_is_rejected_without_merging_profiles(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-EMAIL-PERSON',
            'nombre' => 'Alumno Original',
            'email' => 'alumno.before.person@example.test',
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno);
        $docente = Docente::create([
            'no_empleado' => 'DOC-EMAIL-PERSON-01',
            'nombre' => 'Docente Diferente',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'correo.ocupado.persona@example.test',
            'activo' => 1,
        ]);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('nombre', 'Alumno No Persistido')
            ->set('email', $docente->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'nombre' => 'Alumno Original',
            'email' => 'alumno.before.person@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'email' => 'alumno.before.person@example.test',
        ]);
        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'email' => 'correo.ocupado.persona@example.test',
        ]);
    }

    public function test_duplicate_control_number_rejection_does_not_partially_update_linked_account(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-MATRICULA-OWN',
            'nombre' => 'Alumno Antes',
            'email' => 'alumno.matricula.own@example.test',
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno);
        $otroAlumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-MATRICULA-TAKEN',
            'email' => 'alumno.matricula.taken@example.test',
        ]);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('no_control', $otroAlumno->no_control)
            ->set('nombre', 'Cambio No Persistido')
            ->call('guardar')
            ->assertHasErrors(['no_control' => 'unique']);

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'no_control' => 'TST-MATRICULA-OWN',
            'nombre' => 'Alumno Antes',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => $cuenta->name,
            'email' => 'alumno.matricula.own@example.test',
        ]);
    }

    public function test_duplicate_control_number_and_email_are_rejected(): void
    {
        $this->actingAs($this->administrador);
        $existente = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-DUP-001',
            'email' => 'duplicado@example.test',
        ]);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalCrear')
            ->set('id_carrera', $this->carreraActiva->id_carrera)
            ->set('no_control', $existente->no_control)
            ->set('nombre', 'Otro Alumno')
            ->set('apellido_paterno', 'Prueba')
            ->set('email', 'otro@example.test')
            ->call('guardar')
            ->assertHasErrors(['no_control' => 'unique']);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalCrear')
            ->set('id_carrera', $this->carreraActiva->id_carrera)
            ->set('no_control', 'TST-DUP-002')
            ->set('nombre', 'Otro Alumno')
            ->set('apellido_paterno', 'Prueba')
            ->set('email', $existente->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique']);

        $this->assertDatabaseCount('alumnos', 1);
    }

    public function test_new_alumno_cannot_be_assigned_to_an_inactive_career(): void
    {
        $this->actingAs($this->administrador);
        $carreraInactiva = $this->crearCarrera('TST-ALU-INACT', false);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalCrear')
            ->set('id_carrera', $carreraInactiva->id_carrera)
            ->set('no_control', 'TST-2026-003')
            ->set('nombre', 'Alumno Inactivo')
            ->set('apellido_paterno', 'Prueba')
            ->set('email', 'alumno.inactivo@example.test')
            ->call('guardar')
            ->assertHasErrors('id_carrera');

        $this->assertDatabaseMissing('alumnos', ['no_control' => 'TST-2026-003']);
    }

    public function test_edit_preserves_an_existing_inactive_career_and_rejects_a_different_inactive_career(): void
    {
        $this->actingAs($this->administrador);
        $carreraOriginal = $this->crearCarrera('TST-ALU-ORIG', false);
        $otraInactiva = $this->crearCarrera('TST-ALU-OTHER', false);
        $alumno = $this->crearAlumno($carreraOriginal, ['no_control' => 'TST-LEGACY-01']);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->assertSee('Inactiva, asignación actual')
            ->set('nombre', 'Alumno Conservado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $carreraOriginal->id_carrera,
            'nombre' => 'Alumno Conservado',
        ]);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $otraInactiva->id_carrera)
            ->call('guardar')
            ->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $carreraOriginal->id_carrera,
        ]);
    }

    public function test_career_change_without_inscriptions_waits_for_confirmation_and_saves_all_approved_form_changes(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CAREER-SIN-INS', true);
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-CHG-NOINS-01',
            'nombre' => 'Nombre Antes',
            'email' => 'alumno.sin.inscripciones@example.test',
            'activo' => 0,
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno, false);
        $passwordOriginal = $cuenta->password;
        $rolOriginal = $cuenta->FK_id_rol;

        $this->actingAs($this->administrador);

        $componente = Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->set('nombre', 'Nombre Después')
            ->set('email', 'alumno.sin.inscripciones.nuevo@example.test')
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera')
            ->assertSee('Carrera '.$this->carreraActiva->clave)
            ->assertSee('Carrera '.$carreraNueva->clave)
            ->assertSee('no realizará equivalencias ni traslados automáticos');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
            'nombre' => 'Nombre Antes',
            'email' => 'alumno.sin.inscripciones@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'email' => 'alumno.sin.inscripciones@example.test',
        ]);

        $componente->call('confirmarCambioCarrera')->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $carreraNueva->id_carrera,
            'nombre' => 'Nombre Después',
            'email' => 'alumno.sin.inscripciones.nuevo@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => 'Nombre Después Prueba',
            'email' => 'alumno.sin.inscripciones.nuevo@example.test',
            'activo' => 0,
            'FK_id_rol' => $rolOriginal,
            'id_alumno' => $alumno->id_alumno,
        ]);
        $this->assertSame($passwordOriginal, User::findOrFail($cuenta->id)->password);
        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'no_control' => 'TST-CHG-NOINS-01',
            'activo' => 0,
        ]);
    }

    public function test_career_change_with_only_finalized_period_inscriptions_preserves_academic_records(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-HIST', true);
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-CAMBIO-HIST-01',
            'email' => 'alumno.historial@example.test',
        ]);
        $registros = $this->crearInscripcionAcademica($alumno, $this->carreraActiva, 'Finalizado', true);

        $this->actingAs($this->administrador);

        $componente = Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);

        $componente->call('confirmarCambioCarrera')->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $carreraNueva->id_carrera,
        ]);
        $this->assertDatabaseHas('inscripciones', ['id_inscripcion' => $registros['id_inscripcion']]);
        $this->assertDatabaseHas('calificaciones', ['id_calificacion' => $registros['id_calificacion']]);
        $this->assertDatabaseHas('historial_academico', ['id_historial' => $registros['id_historial']]);
        $this->assertDatabaseHas('inscripcion_grupos', ['id_inscripcion_grupo' => $registros['id_inscripcion_grupo']]);
        $this->assertDatabaseHas('grupos', ['id_grupo' => $registros['id_grupo'], 'id_materia' => $registros['id_materia']]);
        $this->assertDatabaseHas('materias', [
            'id_materia' => $registros['id_materia'],
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
        $this->assertDatabaseHas('historial_academico', [
            'id_historial' => $registros['id_historial'],
            'id_materia' => $registros['id_materia'],
            'id_periodo' => $registros['id_periodo'],
        ]);
        $this->assertDatabaseHas('avance_creditos', [
            'id_avance' => $registros['id_avance'],
            'id_alumno' => $alumno->id_alumno,
            'id_periodo' => $registros['id_periodo'],
        ]);
        $this->assertDatabaseCount('inscripciones', 1);
    }

    public function test_active_period_inscription_blocks_career_change_even_if_its_group_is_inactive(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-ACT', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-CHG-ACT-01']);
        $this->crearInscripcionAcademica($alumno, $this->carreraActiva, 'Activo', false);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->set('nombre', 'Cambio No Permitido')
            ->call('guardar')
            ->assertHasErrors('id_carrera')
            ->assertSee('inscripciones asociadas a un periodo escolar activo');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
            'nombre' => $alumno->nombre,
        ]);
    }

    public function test_upcoming_period_inscription_blocks_career_change(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-PROX', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-CHG-PROX-01']);
        $this->crearInscripcionAcademica($alumno, $this->carreraActiva, 'Próximo', true);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertHasErrors('id_carrera')
            ->assertSee('inscripciones asociadas a un periodo escolar próximo');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
    }

    public function test_inactive_destination_career_is_rejected_before_confirmation(): void
    {
        $carreraInactiva = $this->crearCarrera('TST-CARRERA-INACT', false);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-CHG-INACT-01']);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraInactiva->id_carrera)
            ->call('guardar')
            ->assertHasErrors('id_carrera')
            ->assertDontSee('Confirmar cambio de carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
    }

    public function test_cancelling_career_confirmation_persists_none_of_the_pending_form_changes(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-CANCEL', true);
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-CHG-CANCEL-01',
            'nombre' => 'Nombre Original',
            'email' => 'alumno.cancelado@example.test',
        ]);
        $cuenta = $this->crearCuentaVinculada($alumno);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->set('nombre', 'Nombre Pendiente')
            ->set('email', 'alumno.cancelado.nuevo@example.test')
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera')
            ->call('cancelarConfirmacionCambioCarrera')
            ->assertSet('id_carrera', (string) $this->carreraActiva->id_carrera)
            ->assertSet('confirmacionCambioCarreraPendiente', null);

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
            'nombre' => 'Nombre Original',
            'email' => 'alumno.cancelado@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => $cuenta->name,
            'email' => 'alumno.cancelado@example.test',
        ]);
    }

    public function test_destination_career_deactivated_before_confirmation_is_rejected(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-DEST', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-CHG-DEST-01']);

        $this->actingAs($this->administrador);

        $componente = Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera');

        $carreraNueva->activo = false;
        $carreraNueva->save();

        $componente->call('confirmarCambioCarrera')->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
    }

    public function test_changed_original_career_before_confirmation_is_rejected(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-NUEVA', true);
        $carreraConcurrente = $this->crearCarrera('TST-CARRERA-ORIG', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-ORIG-CONCUR-01']);

        $this->actingAs($this->administrador);

        $componente = Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera');

        $alumno->id_carrera = $carreraConcurrente->id_carrera;
        $alumno->save();

        $componente->call('confirmarCambioCarrera')->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $carreraConcurrente->id_carrera,
        ]);
    }

    public function test_new_active_inscription_before_confirmation_blocks_career_change(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-INS', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-INS-APARECE']);

        $this->actingAs($this->administrador);

        $componente = Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera');

        $registros = $this->crearInscripcionAcademica($alumno, $this->carreraActiva, 'Activo', true);

        $componente->call('confirmarCambioCarrera')->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
        $this->assertDatabaseHas('inscripciones', ['id_inscripcion' => $registros['id_inscripcion']]);
    }

    public function test_unknown_period_state_blocks_career_change(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-UNKNOWN', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-ESTADO-UNK-01']);
        $this->crearInscripcionAcademica($alumno, $this->carreraActiva, 'En revisión', false);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertHasErrors('id_carrera')
            ->assertSee('estado desconocido');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
    }

    public function test_inconsistent_finalized_period_state_blocks_career_change(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-STATE', true);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-ESTADO-INC-01']);
        $this->crearInscripcionAcademica($alumno, $this->carreraActiva, 'Finalizado', true, true);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertHasErrors('id_carrera')
            ->assertSee('estado activo inconsistente');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
    }

    public function test_only_users_with_edit_permission_can_confirm_a_career_change(): void
    {
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-CAMBIO-SIN-PERMISO']);
        $usuarioSoloConsulta = $this->crearUsuario(Role::where('nombre', 'Alumno')->firstOrFail(), 'alumno');

        $this->actingAs($usuarioSoloConsulta);

        Livewire::test(GestionAlumnos::class)
            ->call('confirmarCambioCarrera')
            ->assertStatus(403);

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
        ]);
    }

    public function test_form_changes_after_the_confirmation_prompt_require_a_new_confirmation(): void
    {
        $carreraNueva = $this->crearCarrera('TST-CARRERA-DATOS', true);
        $alumno = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-CHG-DATA-01',
            'nombre' => 'Nombre Inicial',
        ]);

        $this->actingAs($this->administrador);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->set('id_carrera', $carreraNueva->id_carrera)
            ->call('guardar')
            ->assertSee('Confirmar cambio de carrera')
            ->set('nombre', 'Nombre Cambiado Después')
            ->call('confirmarCambioCarrera')
            ->assertHasErrors('id_carrera');

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'id_carrera' => $this->carreraActiva->id_carrera,
            'nombre' => 'Nombre Inicial',
        ]);
    }

    public function test_search_and_career_filter_combine_and_the_list_is_paginated(): void
    {
        $this->actingAs($this->administrador);
        $otraCarrera = $this->crearCarrera('TST-ALU-SEARCH', true);

        for ($indice = 1; $indice <= 16; $indice++) {
            $this->crearAlumno($this->carreraActiva, [
                'no_control' => sprintf('A%07d', $indice),
                'nombre' => sprintf('Alumno %02d', $indice),
            ]);
        }

        $alumnoOtraCarrera = $this->crearAlumno($otraCarrera, [
            'no_control' => 'B0000001',
            'nombre' => 'Coincidencia ajena',
        ]);

        $componente = Livewire::test(GestionAlumnos::class)
            ->set('filtroCarrera', (string) $this->carreraActiva->id_carrera)
            ->set('search', 'A0000007')
            ->assertSee('A0000007')
            ->assertDontSee($alumnoOtraCarrera->no_control);

        $componente
            ->set('search', '')
            ->assertViewHas('alumnos', fn ($alumnos) => $alumnos->total() === 16
                && $alumnos->count() === 15
                && $alumnos->currentPage() === 1)
            ->call('nextPage')
            ->assertViewHas('alumnos', fn ($alumnos) => $alumnos->total() === 16
                && $alumnos->count() === 1
                && $alumnos->currentPage() === 2)
            ->assertSee('A0000016')
            ->assertDontSee('A0000001');
    }

    public function test_control_escolar_can_create_and_change_status_but_view_only_role_cannot_mutate_alumnos(): void
    {
        $rolControlEscolar = Role::where('nombre', 'Control Escolar')->firstOrFail();
        $usuarioControlEscolar = $this->crearUsuario($rolControlEscolar, 'servicios_escolares');

        $this->actingAs($usuarioControlEscolar);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalCrear')
            ->set('id_carrera', $this->carreraActiva->id_carrera)
            ->set('no_control', 'TST-CONTROL-01')
            ->set('nombre', 'Alta Control Escolar')
            ->set('apellido_paterno', 'Prueba')
            ->set('email', 'control.alta@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $alumnoControl = Alumno::where('no_control', 'TST-CONTROL-01')->firstOrFail();

        $componenteAlumnos = Livewire::test(GestionAlumnos::class)
            ->call('desactivar', $alumnoControl->id_alumno)
            ->assertHasNoErrors()
            ->assertSee('Alumno desactivado correctamente.');
        $this->assertDatabaseHas('alumnos', ['id_alumno' => $alumnoControl->id_alumno, 'activo' => 0]);

        $componenteAlumnos->call('desactivar', $alumnoControl->id_alumno)
            ->assertHasNoErrors()
            ->assertSee('El alumno ya estaba inactivo.');
        $this->assertDatabaseHas('alumnos', ['id_alumno' => $alumnoControl->id_alumno, 'activo' => 0]);

        $componenteAlumnos
            ->call('reactivar', $alumnoControl->id_alumno)
            ->assertHasNoErrors()
            ->assertSee('Alumno reactivado correctamente.');
        $this->assertDatabaseHas('alumnos', ['id_alumno' => $alumnoControl->id_alumno, 'activo' => 1]);
        $componenteAlumnos->call('reactivar', $alumnoControl->id_alumno)
            ->assertHasNoErrors()
            ->assertSee('El alumno ya estaba activo.');
        $this->assertDatabaseHas('alumnos', ['id_alumno' => $alumnoControl->id_alumno, 'activo' => 1]);
        $this->assertDatabaseMissing('users', ['id_alumno' => $alumnoControl->id_alumno]);

        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-VIEW-01']);
        $alumnoInactivo = $this->crearAlumno($this->carreraActiva, [
            'no_control' => 'TST-VIEW-02',
            'activo' => 0,
        ]);
        $rolSoloConsulta = Role::where('nombre', 'Alumno')->firstOrFail();
        $usuarioSoloConsulta = $this->crearUsuario($rolSoloConsulta, 'alumno');

        $this->actingAs($usuarioSoloConsulta);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalCrear')
            ->assertStatus(403);

        Livewire::test(GestionAlumnos::class)
            ->call('abrirModalEditar', $alumno->id_alumno)
            ->assertStatus(403);

        Livewire::test(GestionAlumnos::class)
            ->call('desactivar', $alumno->id_alumno)
            ->assertStatus(403);

        Livewire::test(GestionAlumnos::class)
            ->call('eliminar', $alumno->id_alumno)
            ->assertStatus(403);

        Livewire::test(GestionAlumnos::class)
            ->call('reactivar', $alumnoInactivo->id_alumno)
            ->assertStatus(403);

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumnoInactivo->id_alumno,
            'activo' => 0,
        ]);
    }

    public function test_deactivating_a_linked_alumno_preserves_academic_records_and_account(): void
    {
        $this->actingAs($this->administrador);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-REL-001']);
        $usuarioAlumno = $this->crearUsuario(
            Role::where('nombre', 'Alumno')->firstOrFail(),
            'alumno',
            $alumno->id_alumno
        );
        $passwordOriginal = $usuarioAlumno->password;
        $rolOriginal = $usuarioAlumno->FK_id_rol;
        $periodo = PeriodoEscolar::create([
            'clave' => 'TST-PER-01',
            'nombre' => 'Periodo de prueba',
            'estado' => 'Finalizado',
            'activo' => 0,
        ]);
        $materia = Materia::create([
            'id_carrera' => $this->carreraActiva->id_carrera,
            'clave' => 'TST-MAT-01',
            'nombre' => 'Materia de prueba',
            'creditos' => 5,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'activo' => 1,
        ]);
        $grupo = Grupo::create([
            'id_materia' => $materia->id_materia,
            'id_docente' => null,
            'id_periodo' => $periodo->id_periodo,
            'nombre' => 'TST-G1',
            'cupo_maximo' => 30,
            'activo' => 1,
        ]);
        $inscripcionId = DB::table('inscripciones')->insertGetId([
            'id_alumno' => $alumno->id_alumno,
            'id_grupo' => $grupo->id_grupo,
            'fecha_inscripcion' => '2026-10-21',
            'estatus' => 'ordinario',
            'calificacion_final' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $calificacionId = DB::table('calificaciones')->insertGetId([
            'id_inscripcion' => $inscripcionId,
            'id_capturada_por' => null,
            'parcial' => 1,
            'calificacion' => 9.0,
            'observaciones' => 'Registro de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $historialId = DB::table('historial_academico')->insertGetId([
            'id_alumno' => $alumno->id_alumno,
            'id_materia' => $materia->id_materia,
            'id_periodo' => $periodo->id_periodo,
            'calificacion' => 9.0,
            'tipo_acreditacion' => 'ordinario',
            'aprobada' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $avanceId = DB::table('avance_creditos')->insertGetId([
            'id_alumno' => $alumno->id_alumno,
            'id_periodo' => $periodo->id_periodo,
            'creditos_acumulados' => 5,
            'creditos_periodo' => 5,
            'promedio_periodo' => 9.0,
            'promedio_general' => 9.0,
            'materias_acreditadas' => 1,
            'materias_reprobadas' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inscripcionGrupoId = DB::table('inscripcion_grupos')->insertGetId([
            'id_inscripcion' => $inscripcionId,
            'id_grupo' => $grupo->id_grupo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(GestionAlumnos::class)
            ->assertSee('Desactivar')
            ->assertSee('¿Desactivar este alumno?')
            ->assertDontSee('Eliminar alumno')
            ->call('desactivar', $alumno->id_alumno)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'activo' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $usuarioAlumno->id,
            'id_alumno' => $alumno->id_alumno,
            'activo' => 1,
        ]);
        $this->assertSame($passwordOriginal, User::findOrFail($usuarioAlumno->id)->password);
        $this->assertSame($rolOriginal, User::findOrFail($usuarioAlumno->id)->FK_id_rol);
        $this->assertDatabaseHas('inscripciones', ['id_inscripcion' => $inscripcionId]);
        $this->assertDatabaseHas('calificaciones', ['id_calificacion' => $calificacionId]);
        $this->assertDatabaseHas('historial_academico', ['id_historial' => $historialId]);
        $this->assertDatabaseHas('avance_creditos', ['id_avance' => $avanceId]);
        $this->assertDatabaseHas('inscripcion_grupos', ['id_inscripcion_grupo' => $inscripcionGrupoId]);
    }

    public function test_legacy_delete_action_only_deactivates_an_unlinked_alumno(): void
    {
        $this->actingAs($this->administrador);
        $alumno = $this->crearAlumno($this->carreraActiva, ['no_control' => 'TST-LEGACY-DEACT']);

        Livewire::test(GestionAlumnos::class)
            ->call('eliminar', $alumno->id_alumno)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'no_control' => 'TST-LEGACY-DEACT',
            'activo' => 0,
        ]);
        $this->assertDatabaseCount('alumnos', 1);
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

    private function crearAlumno(Carrera $carrera, array $datos = []): Alumno
    {
        $this->secuenciaAlumno++;
        $sufijo = str_pad((string) $this->secuenciaAlumno, 5, '0', STR_PAD_LEFT);

        return Alumno::create(array_merge([
            'id_carrera' => $carrera->id_carrera,
            'no_control' => 'TST'.$sufijo,
            'nombre' => 'Alumno '.$sufijo,
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'alumno.'.$sufijo.'@example.test',
            'activo' => 1,
        ], $datos));
    }

    private function crearUsuario(Role $rol, string $rolLegacy, ?int $idAlumno = null): User
    {
        $this->secuenciaUsuario++;

        return User::create([
            'name' => 'Usuario de prueba '.$this->secuenciaUsuario,
            'email' => 'usuario.'.$rolLegacy.'.'.$this->secuenciaUsuario.'@example.test',
            'password' => 'password-de-prueba',
            'rol' => $rolLegacy,
            'FK_id_rol' => $rol->id_rol,
            'id_alumno' => $idAlumno,
            'activo' => 1,
        ]);
    }

    private function crearCuentaVinculada(Alumno $alumno, bool $activa = true): User
    {
        $cuenta = $this->crearUsuario(
            Role::where('nombre', 'Alumno')->firstOrFail(),
            'alumno',
            (int) $alumno->id_alumno
        );
        $cuenta->name = trim($alumno->nombre.' '.$alumno->apellido_paterno.' '.trim((string) $alumno->apellido_materno));
        $cuenta->email = $alumno->email;
        $cuenta->activo = $activa;
        $cuenta->save();

        return $cuenta;
    }

    /**
     * @return array{
     *     id_inscripcion: int,
     *     id_grupo: int,
     *     id_materia: int,
     *     id_periodo: int,
     *     id_calificacion: int,
     *     id_historial: int,
     *     id_avance: int,
     *     id_inscripcion_grupo: int
     * }
     */
    private function crearInscripcionAcademica(
        Alumno $alumno,
        Carrera $carreraMateria,
        string $estadoPeriodo,
        bool $grupoActivo,
        ?bool $periodoActivo = null
    ): array {
        $this->secuenciaRegistroAcademico++;
        $sufijo = str_pad((string) $this->secuenciaRegistroAcademico, 5, '0', STR_PAD_LEFT);
        $periodoActivo ??= $estadoPeriodo === 'Activo';

        $periodo = PeriodoEscolar::create([
            'clave' => 'TST-P-'.$sufijo,
            'nombre' => 'Periodo prueba '.$sufijo,
            'estado' => $estadoPeriodo,
            'activo' => $periodoActivo,
        ]);
        $materia = Materia::create([
            'id_carrera' => $carreraMateria->id_carrera,
            'clave' => 'TST-M-'.$sufijo,
            'nombre' => 'Materia prueba '.$sufijo,
            'creditos' => 5,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'activo' => 1,
        ]);
        $grupo = Grupo::create([
            'id_materia' => $materia->id_materia,
            'id_docente' => null,
            'id_periodo' => $periodo->id_periodo,
            'nombre' => 'G'.$sufijo,
            'cupo_maximo' => 30,
            'activo' => $grupoActivo,
        ]);
        $inscripcionId = DB::table('inscripciones')->insertGetId([
            'id_alumno' => $alumno->id_alumno,
            'id_grupo' => $grupo->id_grupo,
            'fecha_inscripcion' => '2026-10-21',
            'estatus' => 'ordinario',
            'calificacion_final' => 8.0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $calificacionId = DB::table('calificaciones')->insertGetId([
            'id_inscripcion' => $inscripcionId,
            'id_capturada_por' => null,
            'parcial' => 1,
            'calificacion' => 8.0,
            'observaciones' => 'Calificación de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $historialId = DB::table('historial_academico')->insertGetId([
            'id_alumno' => $alumno->id_alumno,
            'id_materia' => $materia->id_materia,
            'id_periodo' => $periodo->id_periodo,
            'calificacion' => 8.0,
            'tipo_acreditacion' => 'ordinario',
            'aprobada' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $avanceId = DB::table('avance_creditos')->insertGetId([
            'id_alumno' => $alumno->id_alumno,
            'id_periodo' => $periodo->id_periodo,
            'creditos_acumulados' => 5,
            'creditos_periodo' => 5,
            'promedio_periodo' => 8.0,
            'promedio_general' => 8.0,
            'materias_acreditadas' => 1,
            'materias_reprobadas' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $inscripcionGrupoId = DB::table('inscripcion_grupos')->insertGetId([
            'id_inscripcion' => $inscripcionId,
            'id_grupo' => $grupo->id_grupo,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'id_inscripcion' => $inscripcionId,
            'id_grupo' => (int) $grupo->id_grupo,
            'id_materia' => (int) $materia->id_materia,
            'id_periodo' => (int) $periodo->id_periodo,
            'id_calificacion' => $calificacionId,
            'id_historial' => $historialId,
            'id_avance' => $avanceId,
            'id_inscripcion_grupo' => $inscripcionGrupoId,
        ];
    }
}
