<?php

namespace Tests\Feature;

use App\Livewire\GestionDocentes;
use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\JefeCarrera;
use App\Models\Materia;
use App\Models\PeriodoEscolar;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class GestionDocentesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'control_escolar_test') {
            throw new RuntimeException('GestionDocentesTest solo puede ejecutarse en la base aislada control_escolar_test.');
        }
    }

    public function test_materias_asignadas_counts_distinct_subjects_in_all_active_periods_only(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-KPI-01', 'docente.kpi@example.test');
        $materiaUno = $this->crearMateria($carrera, 'TST-KPI-MAT-01');
        $materiaDos = $this->crearMateria($carrera, 'TST-KPI-MAT-02');
        $materiaTres = $this->crearMateria($carrera, 'TST-KPI-MAT-03');
        $periodoActivoUno = $this->crearPeriodo('TST-KPI-PER-01', true);
        $periodoActivoDos = $this->crearPeriodo('TST-KPI-PER-02', true);
        $periodoInactivo = $this->crearPeriodo('TST-KPI-PER-03', false);

        $this->crearGrupo($materiaUno, $periodoActivoUno, $docente, true, 'TST-KPI-G-01');
        $this->crearGrupo($materiaUno, $periodoActivoDos, $docente, true, 'TST-KPI-G-02');
        $this->crearGrupo($materiaDos, $periodoActivoDos, $docente, true, 'TST-KPI-G-03');
        $this->crearGrupo($materiaTres, $periodoActivoUno, $docente, false, 'TST-KPI-G-04');
        $this->crearGrupo($materiaTres, $periodoInactivo, $docente, true, 'TST-KPI-G-05');
        $this->crearGrupo($materiaTres, $periodoActivoUno, null, true, 'TST-KPI-G-06');

        Livewire::test(GestionDocentes::class)
            ->assertSet('materiasAsignadasPeriodoActivo', 2)
            ->assertSee('Materias asignadas · periodo activo');
    }

    public function test_materias_asignadas_shows_zero_and_refreshes_after_new_active_group(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-KPI-02', 'docente.kpi.refresh@example.test');
        $materia = $this->crearMateria($carrera, 'TST-KPI-MAT-04');
        $periodoInactivo = $this->crearPeriodo('TST-KPI-PER-04', false);
        $this->crearGrupo($materia, $periodoInactivo, $docente, true, 'TST-KPI-G-07');

        $componente = Livewire::test(GestionDocentes::class)
            ->assertSet('materiasAsignadasPeriodoActivo', 0);

        $periodoActivo = $this->crearPeriodo('TST-KPI-PER-05', true);
        $this->crearGrupo($materia, $periodoActivo, $docente, true, 'TST-KPI-G-08');

        $componente
            ->call('cargarDocentes')
            ->assertSet('materiasAsignadasPeriodoActivo', 1);
    }

    public function test_editing_a_linked_docente_synchronizes_personal_data_and_preserves_account_fields(): void
    {
        $this->actingAs($this->crearAdministrador());
        $rolDocente = Role::where('nombre', 'Docente')->firstOrFail();
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-01', 'original@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $cuenta = $this->crearCuenta($rolDocente, $docente, 'original@example.test');
        $passwordHash = $cuenta->password;

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('nombre', 'Nombre Actualizado')
            ->set('apellido_paterno', 'Apellido Nuevo')
            ->set('apellido_materno', 'Materno Nuevo')
            ->set('email', 'actualizado@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Nombre Actualizado',
            'apellido_paterno' => 'Apellido Nuevo',
            'apellido_materno' => 'Materno Nuevo',
            'email' => 'actualizado@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_docente' => $docente->id_docente,
            'name' => 'Nombre Actualizado Apellido Nuevo Materno Nuevo',
            'email' => 'actualizado@example.test',
            'activo' => 0,
            'FK_id_rol' => $rolDocente->id_rol,
        ]);
        $this->assertSame($passwordHash, User::findOrFail($cuenta->id)->password);
        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carrera->id_carrera,
        ]);
    }

    public function test_editing_an_unlinked_docente_does_not_create_a_user_account(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-02', 'sin-cuenta@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $cuentasAntes = User::count();

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('nombre', 'Docente Sin Cuenta')
            ->set('email', 'sin-cuenta.actualizado@example.test')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Docente Sin Cuenta',
            'email' => 'sin-cuenta.actualizado@example.test',
        ]);
        $this->assertSame($cuentasAntes, User::count());
        $this->assertDatabaseMissing('users', ['id_docente' => $docente->id_docente]);
    }

    public function test_linked_docente_can_keep_its_own_account_email_when_other_personal_fields_change(): void
    {
        $this->actingAs($this->crearAdministrador());
        $rolDocente = Role::where('nombre', 'Docente')->firstOrFail();
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-03', 'conservar@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $cuenta = $this->crearCuenta($rolDocente, $docente, 'conservar@example.test');

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('nombre', 'Nombre Sin Correo')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Nombre Sin Correo',
            'email' => 'conservar@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => 'Nombre Sin Correo Apellido DOC-SYNC-03',
            'email' => 'conservar@example.test',
        ]);
    }

    public function test_email_used_by_another_user_rejects_the_update_without_partial_changes(): void
    {
        $this->actingAs($this->crearAdministrador());
        $rolDocente = Role::where('nombre', 'Docente')->firstOrFail();
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-04', 'docente.original@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $cuenta = $this->crearCuenta($rolDocente, $docente, 'docente.original@example.test');
        $otraCuenta = $this->crearCuenta($rolDocente, null, 'correo-ocupado@example.test');

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('nombre', 'Cambio No Persistido')
            ->set('email', $otraCuenta->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('El correo ya está registrado para otra cuenta o persona.');

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Nombre DOC-SYNC-04',
            'email' => 'docente.original@example.test',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'name' => 'Nombre DOC-SYNC-04 Apellido DOC-SYNC-04',
            'email' => 'docente.original@example.test',
        ]);
    }

    public function test_email_used_by_another_docente_rejects_the_update_without_partial_changes(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-05', 'docente.uno@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $otroDocente = $this->crearDocente('DOC-SYNC-06', 'docente.dos@example.test');

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('apellido_paterno', 'Cambio No Persistido')
            ->set('email', $otroDocente->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('El correo ya está registrado para otra cuenta o persona.');

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'apellido_paterno' => 'Apellido DOC-SYNC-05',
            'email' => 'docente.uno@example.test',
        ]);
        $this->assertDatabaseHas('docentes', [
            'id_docente' => $otroDocente->id_docente,
            'email' => 'docente.dos@example.test',
        ]);
    }

    public function test_email_used_by_another_person_type_rejects_the_update(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-07', 'docente.tres@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $alumno = Alumno::create([
            'id_carrera' => $carrera->id_carrera,
            'no_control' => 'ALU-SYNC-01',
            'nombre' => 'Alumno Existente',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'correo-alumno@example.test',
            'activo' => 1,
        ]);

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('email', $alumno->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('El correo ya está registrado para otra cuenta o persona.');

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'email' => 'docente.tres@example.test',
        ]);
        $this->assertDatabaseHas('alumnos', [
            'id_alumno' => $alumno->id_alumno,
            'email' => 'correo-alumno@example.test',
        ]);
    }

    public function test_same_employee_jefe_profile_does_not_block_its_linked_docente_email(): void
    {
        $this->actingAs($this->crearAdministrador());
        $rolDocente = Role::where('nombre', 'Docente')->firstOrFail();
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-08', 'misma-persona@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $jefeCarrera = JefeCarrera::create([
            'id_carrera' => $carrera->id_carrera,
            'nombre' => $docente->nombre,
            'apellido_paterno' => $docente->apellido_paterno,
            'apellido_materno' => null,
            'no_empleado' => $docente->no_empleado,
            'email' => $docente->email,
            'fecha_inicio' => '2026-01-01',
            'activo' => 1,
        ]);
        $cuenta = $this->crearCuenta($rolDocente, $docente, $docente->email);

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('nombre', 'Nombre Actualizado')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_docente' => $docente->id_docente,
            'name' => 'Nombre Actualizado Apellido DOC-SYNC-08',
            'email' => 'misma-persona@example.test',
        ]);
        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefeCarrera->id_jefe_carrera,
            'no_empleado' => $docente->no_empleado,
            'email' => 'misma-persona@example.test',
        ]);
    }

    public function test_email_on_a_different_jefe_carrera_remains_a_duplicate(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-SYNC-09', 'docente.antes@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $jefeCarrera = JefeCarrera::create([
            'id_carrera' => $carrera->id_carrera,
            'nombre' => 'Jefe de Carrera Distinto',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'no_empleado' => 'JEFE-OTRO-01',
            'email' => 'correo-de-otra-persona@example.test',
            'fecha_inicio' => '2026-01-01',
            'activo' => 1,
        ]);

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('email', $jefeCarrera->email)
            ->call('guardar')
            ->assertHasErrors(['email' => 'unique'])
            ->assertSee('El correo ya está registrado para otra cuenta o persona.');

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'email' => 'docente.antes@example.test',
        ]);
        $this->assertDatabaseHas('jefes_carrera', [
            'id_jefe_carrera' => $jefeCarrera->id_jefe_carrera,
            'email' => 'correo-de-otra-persona@example.test',
        ]);
    }

    public function test_editing_a_docente_preserves_and_displays_an_existing_inactive_career(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carreraActiva = $this->crearCarrera();
        $carreraInactiva = $this->crearCarrera(false, 'TST-DOC-CARR-INACT');
        $docente = $this->crearDocente('DOC-CARRERA-01', 'docente.carrera@example.test');
        $docente->carreras()->attach([$carreraActiva->id_carrera, $carreraInactiva->id_carrera]);

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->assertSee('Inactiva · ya asignada')
            ->set('nombre', 'Docente conserva carrera')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carreraActiva->id_carrera,
        ]);
        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carreraInactiva->id_carrera,
        ]);
        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Docente conserva carrera',
        ]);
    }

    public function test_new_inactive_career_cannot_be_assigned_to_a_docente(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carreraActiva = $this->crearCarrera();
        $carreraInactiva = $this->crearCarrera(false, 'TST-DOC-CARR-NUEVA');

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalCrear')
            ->set('nombre', 'Docente carrera inactiva')
            ->set('apellido_paterno', 'Prueba')
            ->set('no_empleado', 'DOC-CARRERA-02')
            ->set('email', 'docente.carrera.inactiva@example.test')
            ->set('carreras_ids', [$carreraActiva->id_carrera, $carreraInactiva->id_carrera])
            ->call('guardar')
            ->assertHasErrors('carreras_ids');

        $this->assertDatabaseMissing('docentes', ['no_empleado' => 'DOC-CARRERA-02']);
        $this->assertDatabaseMissing('carrera_docente', ['id_carrera' => $carreraInactiva->id_carrera]);
    }

    public function test_removing_a_career_with_assignments_requires_confirmation_and_preserves_records(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carreraRetirar = $this->crearCarrera();
        $carreraConservar = $this->crearCarrera(true, 'TST-DOC-CARR-KEEP');
        $docente = $this->crearDocente('DOC-CARRERA-03', 'docente.asignacion@example.test');
        $docente->carreras()->attach([$carreraRetirar->id_carrera, $carreraConservar->id_carrera]);
        $periodo = PeriodoEscolar::create([
            'clave' => 'TST-DOC-PER-CARR',
            'nombre' => 'Periodo de prueba de carrera',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'estado' => 'Activo',
            'activo' => 1,
        ]);
        $materia = Materia::create([
            'id_carrera' => $carreraRetirar->id_carrera,
            'clave' => 'TST-DOC-MAT-CARR',
            'nombre' => 'Materia asignada del docente',
            'creditos' => 5,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'activo' => 1,
        ]);
        $grupo = Grupo::create([
            'id_materia' => $materia->id_materia,
            'id_docente' => $docente->id_docente,
            'id_periodo' => $periodo->id_periodo,
            'nombre' => 'TST-DOC-G-CARR',
            'cupo_maximo' => 30,
            'activo' => 1,
        ]);
        $asignacionId = DB::table('asignaciones_docente')->insertGetId([
            'id_docente' => $docente->id_docente,
            'id_materia' => $materia->id_materia,
            'id_grupo' => $grupo->id_grupo,
            'horas_asignadas' => '4',
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $componente = Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docente->id_docente)
            ->set('nombre', 'Docente pendiente de confirmación')
            ->set('carreras_ids', [$carreraConservar->id_carrera])
            ->call('guardar')
            ->assertSee('Confirma el retiro de carreras con actividad académica')
            ->assertSee('1 asignación(es)')
            ->assertSee('1 grupo(s) como titular');

        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carreraRetirar->id_carrera,
        ]);
        $this->assertDatabaseHas('asignaciones_docente', [
            'id_asignacion' => $asignacionId,
            'id_docente' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Nombre DOC-CARRERA-03',
        ]);

        $componente->call('cancelarRetiroCarreras')->assertHasNoErrors();
        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carreraRetirar->id_carrera,
        ]);

        $componente
            ->set('carreras_ids', [$carreraConservar->id_carrera])
            ->call('guardar')
            ->assertSee('Confirma el retiro de carreras con actividad académica');

        $componente->call('confirmarRetiroCarreras')->assertHasNoErrors();

        $this->assertDatabaseMissing('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carreraRetirar->id_carrera,
        ]);
        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carreraConservar->id_carrera,
        ]);
        $this->assertDatabaseHas('asignaciones_docente', [
            'id_asignacion' => $asignacionId,
            'id_docente' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('grupos', [
            'id_grupo' => $grupo->id_grupo,
            'id_docente' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'nombre' => 'Docente pendiente de confirmación',
        ]);
    }

    public function test_administrator_can_create_a_docente_without_creating_a_user(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalCrear')
            ->set('nombre', 'Alta Docente')
            ->set('apellido_paterno', 'TESCHA')
            ->set('apellido_materno', '')
            ->set('no_empleado', 'DOC-ALTA-01')
            ->set('email', 'docente.alta@example.test')
            ->set('carreras_ids', [$carrera->id_carrera])
            ->call('guardar')
            ->assertHasNoErrors();

        $docente = Docente::where('no_empleado', 'DOC-ALTA-01')->firstOrFail();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carrera->id_carrera,
        ]);
        $this->assertDatabaseMissing('users', ['id_docente' => $docente->id_docente]);
    }

    public function test_deactivation_and_reactivation_preserve_accounts_and_academic_relations(): void
    {
        $this->actingAs($this->crearAdministrador());
        $carrera = $this->crearCarrera();
        $docente = $this->crearDocente('DOC-ESTADO-01', 'docente.estado@example.test');
        $docente->carreras()->attach($carrera->id_carrera);
        $cuenta = $this->crearCuenta(
            Role::where('nombre', 'Docente')->firstOrFail(),
            $docente,
            'docente.estado@example.test'
        );
        $cuenta->activo = 1;
        $cuenta->save();
        $passwordHash = $cuenta->password;

        $periodo = PeriodoEscolar::create([
            'clave' => 'TST-DOC-PER-01',
            'nombre' => 'Periodo de prueba docente',
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'estado' => 'Activo',
            'activo' => 1,
        ]);
        $materia = Materia::create([
            'id_carrera' => $carrera->id_carrera,
            'clave' => 'TST-DOC-MAT-01',
            'nombre' => 'Materia de prueba docente',
            'creditos' => 5,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'activo' => 1,
        ]);
        $grupo = Grupo::create([
            'id_materia' => $materia->id_materia,
            'id_docente' => $docente->id_docente,
            'id_periodo' => $periodo->id_periodo,
            'nombre' => 'TST-DOC-G1',
            'cupo_maximo' => 30,
            'activo' => 1,
        ]);
        $alumno = Alumno::create([
            'id_carrera' => $carrera->id_carrera,
            'no_control' => 'TST-DOC-ALU-01',
            'nombre' => 'Alumno de prueba',
            'apellido_paterno' => 'TESCHA',
            'apellido_materno' => null,
            'email' => 'alumno.docente@example.test',
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
            'id_capturada_por' => $docente->id_docente,
            'parcial' => 1,
            'calificacion' => 8.5,
            'observaciones' => 'Registro de prueba',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $asignacionId = DB::table('asignaciones_docente')->insertGetId([
            'id_docente' => $docente->id_docente,
            'id_materia' => $materia->id_materia,
            'id_grupo' => $grupo->id_grupo,
            'horas_asignadas' => '4',
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actaId = DB::table('actas')->insertGetId([
            'id_grupo' => $grupo->id_grupo,
            'id_docente' => $docente->id_docente,
            'folio' => 'TST-DOC-ACTA-01',
            'tipo_evaluacion' => 'ordinario',
            'fecha_cierre' => null,
            'cerrada' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(GestionDocentes::class)
            ->call('desactivar', $docente->id_docente)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'activo' => 0,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'id_docente' => $docente->id_docente,
            'activo' => 1,
            'FK_id_rol' => $cuenta->FK_id_rol,
        ]);
        $this->assertSame($passwordHash, User::findOrFail($cuenta->id)->password);
        $this->assertDatabaseHas('carrera_docente', [
            'id_docente' => $docente->id_docente,
            'id_carrera' => $carrera->id_carrera,
        ]);
        $this->assertDatabaseHas('grupos', [
            'id_grupo' => $grupo->id_grupo,
            'id_docente' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('asignaciones_docente', [
            'id_asignacion' => $asignacionId,
            'id_docente' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('calificaciones', [
            'id_calificacion' => $calificacionId,
            'id_capturada_por' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('actas', [
            'id_acta' => $actaId,
            'id_docente' => $docente->id_docente,
        ]);
        $this->assertDatabaseHas('inscripciones', [
            'id_inscripcion' => $inscripcionId,
            'id_grupo' => $grupo->id_grupo,
        ]);

        Livewire::test(GestionDocentes::class)
            ->call('reactivar', $docente->id_docente)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'id_docente' => $docente->id_docente,
            'activo' => 1,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $cuenta->id,
            'activo' => 1,
        ]);
    }

    public function test_control_escolar_with_existing_permissions_can_change_docente_status(): void
    {
        $rolControlEscolar = Role::where('nombre', 'Control Escolar')->firstOrFail();
        $usuarioControlEscolar = $this->crearCuenta($rolControlEscolar, null, 'control-escolar.docentes@example.test');
        $usuarioControlEscolar->activo = 1;
        $usuarioControlEscolar->save();
        $docente = $this->crearDocente('DOC-ESTADO-02', 'docente.ce@example.test');

        $this->actingAs($usuarioControlEscolar);

        Livewire::test(GestionDocentes::class)
            ->call('desactivar', $docente->id_docente)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', ['id_docente' => $docente->id_docente, 'activo' => 0]);

        Livewire::test(GestionDocentes::class)
            ->call('reactivar', $docente->id_docente)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', ['id_docente' => $docente->id_docente, 'activo' => 1]);
    }

    public function test_view_only_role_cannot_create_edit_deactivate_or_reactivate_docentes(): void
    {
        $docenteActivo = $this->crearDocente('DOC-PERMISO-01', 'docente.permiso.a@example.test');
        $docenteInactivo = $this->crearDocente('DOC-PERMISO-02', 'docente.permiso.b@example.test');
        $carrera = $this->crearCarrera();
        $docenteInactivo->activo = 0;
        $docenteInactivo->save();
        $usuarioSoloConsulta = $this->crearCuenta(
            Role::where('nombre', 'Docente')->firstOrFail(),
            null,
            'docente.consulta@example.test'
        );
        $usuarioSoloConsulta->activo = 1;
        $usuarioSoloConsulta->save();

        $this->actingAs($usuarioSoloConsulta);

        Livewire::test(GestionDocentes::class)
            ->assertDontSee('Nuevo docente');

        Livewire::test(GestionDocentes::class)
            ->call('abrirModalCrear')
            ->assertStatus(403);
        Livewire::test(GestionDocentes::class)
            ->set('nombre', 'Alta no autorizada')
            ->set('apellido_paterno', 'Prueba')
            ->set('no_empleado', 'DOC-NO-CREADO')
            ->set('email', 'docente.no-creado@example.test')
            ->set('carreras_ids', [$carrera->id_carrera])
            ->call('guardar')
            ->assertStatus(403);
        Livewire::test(GestionDocentes::class)
            ->call('abrirModalEditar', $docenteActivo->id_docente)
            ->assertStatus(403);
        Livewire::test(GestionDocentes::class)
            ->call('desactivar', $docenteActivo->id_docente)
            ->assertStatus(403);
        Livewire::test(GestionDocentes::class)
            ->call('reactivar', $docenteInactivo->id_docente)
            ->assertStatus(403);

        $this->assertDatabaseHas('docentes', ['id_docente' => $docenteActivo->id_docente, 'activo' => 1]);
        $this->assertDatabaseHas('docentes', ['id_docente' => $docenteInactivo->id_docente, 'activo' => 0]);
        $this->assertDatabaseMissing('docentes', ['no_empleado' => 'DOC-NO-CREADO']);
    }

    private function crearMateria(Carrera $carrera, string $clave): Materia
    {
        return Materia::create([
            'id_carrera' => $carrera->id_carrera,
            'clave' => $clave,
            'nombre' => 'Materia '.$clave,
            'creditos' => 5,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'activo' => 1,
        ]);
    }

    private function crearPeriodo(string $clave, bool $activo): PeriodoEscolar
    {
        return PeriodoEscolar::create([
            'clave' => $clave,
            'nombre' => 'Periodo '.$clave,
            'fecha_inicio' => '2026-01-01',
            'fecha_fin' => '2026-12-31',
            'estado' => $activo ? 'Activo' : 'Finalizado',
            'activo' => $activo,
        ]);
    }

    private function crearGrupo(
        Materia $materia,
        PeriodoEscolar $periodo,
        ?Docente $docente,
        bool $activo,
        string $nombre
    ): Grupo {
        return Grupo::create([
            'id_materia' => $materia->id_materia,
            'id_docente' => $docente?->id_docente,
            'id_periodo' => $periodo->id_periodo,
            'nombre' => $nombre,
            'cupo_maximo' => 30,
            'activo' => $activo,
        ]);
    }

    private function crearCarrera(bool $activa = true, string $clave = 'TST-DOC-CARR'): Carrera
    {
        return Carrera::create([
            'clave' => $clave,
            'nombre' => 'Carrera de prueba '.$clave,
            'reticula_oficial' => null,
            'activo' => $activa,
        ]);
    }

    private function crearDocente(string $numeroEmpleado, string $correo): Docente
    {
        return Docente::create([
            'no_empleado' => $numeroEmpleado,
            'nombre' => 'Nombre '.$numeroEmpleado,
            'apellido_paterno' => 'Apellido '.$numeroEmpleado,
            'apellido_materno' => null,
            'email' => $correo,
            'activo' => 1,
        ]);
    }

    private function crearCuenta(Role $rol, ?Docente $docente, string $correo): User
    {
        return User::create([
            'name' => 'Cuenta original',
            'email' => $correo,
            'password' => 'contrasena-de-prueba',
            'rol' => 'docente',
            'FK_id_rol' => $rol->id_rol,
            'id_docente' => $docente?->id_docente,
            'activo' => 0,
        ]);
    }

    private function crearAdministrador(): User
    {
        return User::create([
            'name' => 'Administrador de prueba',
            'email' => 'admin.docentes@example.test',
            'password' => 'contrasena-de-prueba',
            'rol' => 'admin',
            'FK_id_rol' => Role::where('nombre', 'Administrador')->value('id_rol'),
            'activo' => 1,
        ]);
    }
}
