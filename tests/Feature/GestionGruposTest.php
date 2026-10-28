<?php

namespace Tests\Feature;

use App\Livewire\GestionGrupos;
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

class GestionGruposTest extends TestCase
{
    use DatabaseTransactions;

    private User $administrador;

    private Carrera $carrera;

    private Materia $materia;

    private Docente $docente;

    private PeriodoEscolar $periodoActivo;

    private int $secuencia = 0;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getDatabaseName() !== 'control_escolar_test') {
            throw new RuntimeException('GestionGruposTest solo puede ejecutarse en la base aislada control_escolar_test.');
        }

        $rolAdministrador = Role::where('nombre', 'Administrador')->firstOrFail();
        $this->administrador = $this->crearUsuario($rolAdministrador, 'admin');
        $this->carrera = $this->crearCarrera('TST-CARRERA');
        $this->materia = $this->crearMateria($this->carrera, 'TST-MATERIA', true);
        $this->docente = $this->crearDocente('TST-DOCENTE', true);
        $this->periodoActivo = $this->crearPeriodo('TST-PERIODO-ACTIVO', 'Activo');
    }

    public function test_administrator_can_create_and_edit_a_group_with_existing_relationships(): void
    {
        $this->actingAs($this->administrador);

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->set('id_materia', $this->materia->id_materia)
            ->set('id_docente', $this->docente->id_docente)
            ->set('id_periodo', $this->periodoActivo->id_periodo)
            ->set('nombre', 'TST-501')
            ->set('cupo_maximo', 25)
            ->call('guardar')
            ->assertHasNoErrors();

        $grupo = Grupo::where('nombre', 'TST-501')->firstOrFail();
        $this->assertSame((int) $this->materia->id_materia, (int) $grupo->id_materia);
        $this->assertSame((int) $this->docente->id_docente, (int) $grupo->id_docente);
        $this->assertSame((int) $this->periodoActivo->id_periodo, (int) $grupo->id_periodo);

        Livewire::test(GestionGrupos::class)
            ->assertSee('TST-501')
            ->call('abrirModalEditar', $grupo->id_grupo)
            ->set('cupo_maximo', 28)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', [
            'id_grupo' => $grupo->id_grupo,
            'cupo_maximo' => 28,
            'id_materia' => $this->materia->id_materia,
            'id_periodo' => $this->periodoActivo->id_periodo,
        ]);
    }

    public function test_inactive_catalog_entries_cannot_be_used_for_new_groups(): void
    {
        $this->actingAs($this->administrador);
        $materiaInactiva = $this->crearMateria($this->carrera, 'TST-MAT-INACTIVA', false);
        $docenteInactivo = $this->crearDocente('TST-DOC-INACTIVO', false);
        $periodoFinalizado = $this->crearPeriodo('TST-PERIODO-FINAL', 'Finalizado');

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->set('id_materia', $materiaInactiva->id_materia)
            ->set('id_docente', $this->docente->id_docente)
            ->set('id_periodo', $this->periodoActivo->id_periodo)
            ->set('nombre', 'TST-INACT-MATERIA')
            ->call('guardar')
            ->assertHasErrors('id_materia');

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->set('id_materia', $this->materia->id_materia)
            ->set('id_docente', $docenteInactivo->id_docente)
            ->set('id_periodo', $this->periodoActivo->id_periodo)
            ->set('nombre', 'TST-INACT-DOCENTE')
            ->call('guardar')
            ->assertHasErrors('id_docente');

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->set('id_materia', $this->materia->id_materia)
            ->set('id_docente', '')
            ->set('id_periodo', $periodoFinalizado->id_periodo)
            ->set('nombre', 'TST-PERIODO-FINAL')
            ->call('guardar')
            ->assertHasErrors('id_periodo');

        $this->assertDatabaseMissing('grupos', ['nombre' => 'TST-INACT-MATERIA']);
        $this->assertDatabaseMissing('grupos', ['nombre' => 'TST-INACT-DOCENTE']);
        $this->assertDatabaseMissing('grupos', ['nombre' => 'TST-PERIODO-FINAL']);
    }

    public function test_edit_preserves_inactive_historical_relationships_when_other_fields_change(): void
    {
        $this->actingAs($this->administrador);
        $materiaHistorica = $this->crearMateria($this->carrera, 'TST-MAT-HIST', false);
        $docenteHistorico = $this->crearDocente('TST-DOC-HIST', false);
        $periodoFinalizado = $this->crearPeriodo('TST-PERIODO-HIST', 'Finalizado');
        $grupo = Grupo::create([
            'id_materia' => $materiaHistorica->id_materia,
            'id_docente' => $docenteHistorico->id_docente,
            'id_periodo' => $periodoFinalizado->id_periodo,
            'nombre' => 'TST-HIST-01',
            'cupo_maximo' => 20,
            'activo' => 0,
        ]);

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalEditar', $grupo->id_grupo)
            ->set('cupo_maximo', 25)
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', [
            'id_grupo' => $grupo->id_grupo,
            'id_materia' => $materiaHistorica->id_materia,
            'id_docente' => $docenteHistorico->id_docente,
            'id_periodo' => $periodoFinalizado->id_periodo,
            'cupo_maximo' => 25,
        ]);
    }

    public function test_teacher_without_formal_carrera_membership_is_warned_but_not_blocked(): void
    {
        $this->actingAs($this->administrador);

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->set('id_materia', $this->materia->id_materia)
            ->set('id_docente', $this->docente->id_docente)
            ->set('id_periodo', $this->periodoActivo->id_periodo)
            ->set('nombre', 'TST-SIN-PERTENENCIA')
            ->assertSee('no figura como integrante formal')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', [
            'id_materia' => $this->materia->id_materia,
            'id_docente' => $this->docente->id_docente,
            'nombre' => 'TST-SIN-PERTENENCIA',
        ]);
    }

    public function test_duplicate_group_combination_is_warned_but_not_rejected(): void
    {
        $this->actingAs($this->administrador);
        $grupoExistente = $this->crearGrupo($this->materia, $this->periodoActivo, 'TST-DUP-01');

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->set('id_materia', $this->materia->id_materia)
            ->set('id_periodo', $this->periodoActivo->id_periodo)
            ->set('nombre', $grupoExistente->nombre)
            ->assertSee('Posible grupo duplicado')
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertSee('existe otra coincidencia');

        $this->assertDatabaseCount('grupos', 2);
    }

    public function test_filters_combine_and_group_list_uses_fifteen_row_pages(): void
    {
        $this->actingAs($this->administrador);
        $otraCarrera = $this->crearCarrera('TST-CARRERA-B');
        $otraMateria = $this->crearMateria($otraCarrera, 'TST-MATERIA-B', true);

        for ($indice = 1; $indice <= 16; $indice++) {
            $this->crearGrupo(
                $this->materia,
                $this->periodoActivo,
                sprintf('A%02d', $indice)
            );
        }

        $grupoOtraCarrera = $this->crearGrupo($otraMateria, $this->periodoActivo, 'B01');

        $componente = Livewire::test(GestionGrupos::class)
            ->set('filtroCarrera', (string) $this->carrera->id_carrera)
            ->set('filtroMateria', (string) $this->materia->id_materia)
            ->set('filtroPeriodo', (string) $this->periodoActivo->id_periodo)
            ->set('filtroEstado', '1')
            ->set('search', 'A07')
            ->assertSee('A07')
            ->assertDontSee($grupoOtraCarrera->nombre);

        $componente
            ->set('search', '')
            ->assertViewHas('grupos', fn ($grupos) => $grupos->total() === 16
                && $grupos->count() === 15
                && $grupos->currentPage() === 1)
            ->call('setPage', 2, 'gruposPage')
            ->assertViewHas('grupos', fn ($grupos) => $grupos->total() === 16
                && $grupos->count() === 1
                && $grupos->currentPage() === 2)
            ->assertSee('A16')
            ->assertDontSee('A01');
    }

    public function test_groups_with_academic_records_are_deactivated_without_cascading_deletions(): void
    {
        $this->actingAs($this->administrador);
        $alumno = Alumno::create([
            'id_carrera' => $this->carrera->id_carrera,
            'no_control' => 'TST-ALU-GRUPO',
            'nombre' => 'Alumno Grupo',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'alumno.grupo@example.test',
            'activo' => 1,
        ]);
        $grupo = $this->crearGrupo($this->materia, $this->periodoActivo, 'TST-PROTEGIDO');
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
            'calificacion' => 8.5,
            'observaciones' => 'Prueba aislada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $actaId = DB::table('actas')->insertGetId([
            'id_grupo' => $grupo->id_grupo,
            'id_docente' => $this->docente->id_docente,
            'folio' => 'TST-ACTA-01',
            'tipo_evaluacion' => 'ordinario',
            'fecha_cierre' => null,
            'cerrada' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $asignacionId = DB::table('asignaciones_docente')->insertGetId([
            'id_docente' => $this->docente->id_docente,
            'id_materia' => $this->materia->id_materia,
            'id_grupo' => $grupo->id_grupo,
            'horas_asignadas' => '4',
            'activo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(GestionGrupos::class)
            ->call('eliminar', $grupo->id_grupo)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('grupos', ['id_grupo' => $grupo->id_grupo, 'activo' => 0]);
        $this->assertDatabaseHas('inscripciones', ['id_inscripcion' => $inscripcionId]);
        $this->assertDatabaseHas('calificaciones', ['id_calificacion' => $calificacionId]);
        $this->assertDatabaseHas('actas', ['id_acta' => $actaId]);
        $this->assertDatabaseHas('asignaciones_docente', ['id_asignacion' => $asignacionId]);
    }

    public function test_cupo_cannot_be_reduced_below_existing_inscriptions(): void
    {
        $this->actingAs($this->administrador);
        $alumno = Alumno::create([
            'id_carrera' => $this->carrera->id_carrera,
            'no_control' => 'TST-ALU-CUPO',
            'nombre' => 'Alumno Cupo',
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => 'alumno.cupo@example.test',
            'activo' => 1,
        ]);
        $grupo = $this->crearGrupo($this->materia, $this->periodoActivo, 'TST-CUPO', 1);

        DB::table('inscripciones')->insert([
            'id_alumno' => $alumno->id_alumno,
            'id_grupo' => $grupo->id_grupo,
            'fecha_inscripcion' => '2026-10-21',
            'estatus' => 'ordinario',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inscripciones')->insert([
            'id_alumno' => $this->crearAlumno($this->carrera, ['no_control' => 'TST-ALU-CUPO2'])->id_alumno,
            'id_grupo' => $grupo->id_grupo,
            'fecha_inscripcion' => '2026-10-21',
            'estatus' => 'ordinario',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalEditar', $grupo->id_grupo)
            ->set('cupo_maximo', 1)
            ->call('guardar')
            ->assertHasErrors('cupo_maximo');

        $this->assertDatabaseHas('grupos', ['id_grupo' => $grupo->id_grupo, 'cupo_maximo' => 1]);
    }

    public function test_view_only_role_cannot_create_edit_delete_or_toggle_group_status(): void
    {
        $grupo = $this->crearGrupo($this->materia, $this->periodoActivo, 'TST-PERMISO');
        $rolAlumno = Role::where('nombre', 'Alumno')->firstOrFail();
        $usuarioAlumno = $this->crearUsuario($rolAlumno, 'alumno');
        $this->actingAs($usuarioAlumno);

        Livewire::test(GestionGrupos::class)
            ->call('abrirModalCrear')
            ->assertStatus(403);
        Livewire::test(GestionGrupos::class)
            ->call('abrirModalEditar', $grupo->id_grupo)
            ->assertStatus(403);
        Livewire::test(GestionGrupos::class)
            ->call('eliminar', $grupo->id_grupo)
            ->assertStatus(403);
        Livewire::test(GestionGrupos::class)
            ->call('toggleActivo', $grupo->id_grupo)
            ->assertStatus(403);

        $this->assertDatabaseHas('grupos', ['id_grupo' => $grupo->id_grupo, 'activo' => 1]);
    }

    private function crearCarrera(string $clave): Carrera
    {
        return Carrera::create([
            'clave' => $clave,
            'nombre' => 'Carrera '.$clave,
            'reticula_oficial' => null,
            'activo' => 1,
        ]);
    }

    private function crearMateria(Carrera $carrera, string $clave, bool $activa): Materia
    {
        return Materia::create([
            'id_carrera' => $carrera->id_carrera,
            'clave' => $clave,
            'nombre' => 'Materia '.$clave,
            'creditos' => 5,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'activo' => $activa,
        ]);
    }

    private function crearDocente(string $noEmpleado, bool $activo): Docente
    {
        return Docente::create([
            'no_empleado' => $noEmpleado,
            'nombre' => 'Docente '.$noEmpleado,
            'apellido_paterno' => 'Prueba',
            'apellido_materno' => null,
            'email' => strtolower($noEmpleado).'@example.test',
            'activo' => $activo,
        ]);
    }

    private function crearPeriodo(string $clave, string $estado): PeriodoEscolar
    {
        return PeriodoEscolar::create([
            'clave' => $clave,
            'nombre' => 'Periodo '.$clave,
            'fecha_inicio' => null,
            'fecha_fin' => null,
            'estado' => $estado,
            'activo' => $estado === 'Activo' ? 1 : 0,
        ]);
    }

    private function crearGrupo(Materia $materia, PeriodoEscolar $periodo, string $nombre, int $cupo = 30): Grupo
    {
        return Grupo::create([
            'id_materia' => $materia->id_materia,
            'id_docente' => null,
            'id_periodo' => $periodo->id_periodo,
            'nombre' => $nombre,
            'cupo_maximo' => $cupo,
            'activo' => 1,
        ]);
    }

    private function crearAlumno(Carrera $carrera, array $datos): Alumno
    {
        return Alumno::create(array_merge([
            'id_carrera' => $carrera->id_carrera,
            'no_control' => 'TST-ALUMNO-'.(++$this->secuencia),
            'nombre' => 'Alumno de prueba',
            'apellido_paterno' => 'TESCHA',
            'apellido_materno' => null,
            'email' => 'alumno.'.($this->secuencia).'@example.test',
            'activo' => 1,
        ], $datos));
    }

    private function crearUsuario(Role $rol, string $legacyRole): User
    {
        $this->secuencia++;

        return User::create([
            'name' => 'Usuario '.$this->secuencia,
            'email' => 'usuario.'.$this->secuencia.'@example.test',
            'password' => 'test-password',
            'rol' => $legacyRole,
            'FK_id_rol' => $rol->id_rol,
            'activo' => 1,
        ]);
    }
}
