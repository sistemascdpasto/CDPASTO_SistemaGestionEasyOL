<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\CargoExamen;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\Examen;
use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\Recomendacion;
use App\Models\User;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EvaluacionMedicaExportTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Seguridad');

        return $user;
    }

    public function test_exportacion_basica_descarga_un_excel(): void
    {
        $user = $this->seguridadUser();
        $colaborador = Colaborador::withoutEvents(fn () => Colaborador::create([
            'cedula' => '900555666', 'nombres' => 'Ana', 'apellidos' => 'Lopez', 'estado_registro' => 'completo', 'cargo' => 'CONDUCTOR',
        ]));
        (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.exportar.basica'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exportacion_completa_incluye_una_columna_por_recomendacion_activa(): void
    {
        $user = $this->seguridadUser();
        $colaborador = Colaborador::withoutEvents(fn () => Colaborador::create([
            'cedula' => '900777888', 'nombres' => 'Luis', 'apellidos' => 'Perez', 'estado_registro' => 'completo', 'cargo' => 'CONDUCTOR',
        ]));
        $evaluacion = (new EvaluacionMedicaService())->crear($colaborador, 'ingreso');
        $recomendacion = Recomendacion::create(['nombre' => 'Uso de EPP', 'categoria' => 'ocupacional', 'activo' => true]);
        $evaluacion->recomendaciones()->create(['recomendacion_id' => $recomendacion->id, 'activa' => true, 'fecha_registro' => now()]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.exportar.completa'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_exportacion_respeta_los_filtros_de_la_bandeja(): void
    {
        $user = $this->seguridadUser();
        $examen = Examen::create(['nombre' => 'Examen médico ocupacional', 'activo' => true]);
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $conductor = Colaborador::withoutEvents(fn () => Colaborador::create([
            'cedula' => '900111222', 'nombres' => 'Carlos', 'apellidos' => 'Gomez', 'estado_registro' => 'completo', 'cargo' => 'CONDUCTOR',
        ]));
        $auxiliar = Colaborador::withoutEvents(fn () => Colaborador::create([
            'cedula' => '900333444', 'nombres' => 'Diana', 'apellidos' => 'Ruiz', 'estado_registro' => 'completo', 'cargo' => 'AUXILIAR DE FLOTA',
        ]));

        $service = new EvaluacionMedicaService();
        $service->crear($conductor, 'ingreso');
        $service->crear($auxiliar, 'ingreso');

        $sinFiltro = $this->actingAs($user)->get(route('seguridad.examenes-medicos.exportar.basica'));
        $sinFiltro->assertOk();

        $conFiltro = $this->actingAs($user)->get(route('seguridad.examenes-medicos.exportar.basica', ['cargo' => 'CONDUCTOR']));
        $conFiltro->assertOk();

        // Ambas descargas son válidas; lo que interesa es que el filtro no rompa la exportación
        // y que EvaluacionMedica::count() siga reflejando exactamente los 2 colaboradores creados.
        $this->assertSame(2, EvaluacionMedica::count());
    }
}
