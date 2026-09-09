<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\CargoExamen;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\ConceptoAptitud;
use App\Models\Seguridad\Examen;
use App\Models\Seguridad\Recomendacion;
use App\Models\User;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExamenMedicoIndicadorTest extends TestCase
{
    use RefreshDatabase;

    private function seguridadUser(): User
    {
        Role::create(['name' => 'Seguridad', 'guard_name' => 'web']);
        Role::create(['name' => 'Colaborador', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('Seguridad');

        return $user;
    }

    private function colaborador(): Colaborador
    {
        // Este test controla a mano exactamente qué evaluaciones existen
        // (vía EvaluacionMedicaService::crear/crearEgreso) — withoutEvents
        // evita que el ColaboradorExamenObserver de HU-031/037 genere
        // evaluaciones adicionales solo porque el colaborador ya trae cargo.
        return Colaborador::withoutEvents(fn () => Colaborador::create([
            'cedula' => '900'.random_int(100000, 999999),
            'nombres' => 'Test', 'apellidos' => 'Colaborador',
            'estado_registro' => 'completo', 'cargo' => 'CONDUCTOR',
        ]));
    }

    public function test_indicadores_calculan_cobertura_tipo_evaluacion_y_programacion_de_examenes(): void
    {
        $user = $this->seguridadUser();
        $examen = Examen::create(['nombre' => 'Examen médico ocupacional', 'activo' => true]);
        CargoExamen::create(['cargo' => 'CONDUCTOR', 'examen_id' => $examen->id, 'tipo_evaluacion' => 'ingreso', 'obligatorio' => true, 'activo' => true]);

        $service = new EvaluacionMedicaService();

        // Evaluación 1: examen realizado.
        $evaluacion1 = $service->crear($this->colaborador(), 'ingreso');
        $evaluacion1->examenes()->first()->update(['estado' => 'realizado', 'fecha_ejecucion' => now()]);
        $service->recalcularEstado($evaluacion1);

        // Evaluación 2: examen solo programado (no realizado todavía).
        $evaluacion2 = $service->crear($this->colaborador(), 'ingreso');
        $evaluacion2->examenes()->first()->update(['estado' => 'programado', 'fecha_programacion' => now()->addDay()]);
        $service->recalcularEstado($evaluacion2);

        // Evaluación 3 (periódico): pendiente, nunca programada — no debe contar en cobertura.
        $service->crear($this->colaborador(), 'periodico');

        // Evaluación de egreso.
        $service->crearEgreso($this->colaborador());

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.indicadores'));

        $response->assertInertia(function ($page) {
            $page->component('seguridad/examenes-medicos/indicadores')
                ->where('cobertura.programados', 2)
                ->where('cobertura.ejecutados', 1)
                ->where('cobertura.porcentaje', 50);

            // HU-034: terminar evaluacion1 (Ingreso) genera automáticamente
            // su propio Periódico #1 — además del que este test crea a mano
            // para otro colaborador, quedan 2 en total.
            $porTipo = collect($page->toArray()['props']['porTipoEvaluacion'])->keyBy('valor');
            \PHPUnit\Framework\Assert::assertSame(2, $porTipo['ingreso']['cantidad']);
            \PHPUnit\Framework\Assert::assertSame(2, $porTipo['periodico']['cantidad']);
            \PHPUnit\Framework\Assert::assertSame(1, $porTipo['egreso']['cantidad']);

            $programacion = collect($page->toArray()['props']['programacionExamenes'])->keyBy('valor');
            \PHPUnit\Framework\Assert::assertSame(1, $programacion['realizado']['cantidad']);
            \PHPUnit\Framework\Assert::assertSame(1, $programacion['programado']['cantidad']);
        });
    }

    public function test_indicadores_de_concepto_aptitudinal_y_recomendaciones_por_categoria(): void
    {
        $user = $this->seguridadUser();
        $concepto = ConceptoAptitud::create(['nombre' => 'Satisfactorio', 'activo' => true]);
        $recomendacionMedica = Recomendacion::create(['nombre' => 'Uso de corrección óptica', 'categoria' => 'medica', 'activo' => true]);
        $recomendacionOcupacional = Recomendacion::create(['nombre' => 'Uso de EPP', 'categoria' => 'ocupacional', 'activo' => true]);
        $recomendacionHabitos = Recomendacion::create(['nombre' => 'Actividad física aeróbica', 'categoria' => 'habitos', 'activo' => true]);

        $service = new EvaluacionMedicaService();
        $evaluacionA = $service->crear($this->colaborador(), 'ingreso');
        $evaluacionA->update(['concepto_aptitud_id' => $concepto->id]);
        $evaluacionA->recomendaciones()->create(['recomendacion_id' => $recomendacionMedica->id, 'activa' => true, 'fecha_registro' => now()]);
        $evaluacionA->recomendaciones()->create(['recomendacion_id' => $recomendacionOcupacional->id, 'activa' => true, 'fecha_registro' => now()]);

        $evaluacionB = $service->crear($this->colaborador(), 'ingreso');
        $evaluacionB->update(['concepto_aptitud_id' => $concepto->id]);
        $evaluacionB->recomendaciones()->create(['recomendacion_id' => $recomendacionHabitos->id, 'activa' => true, 'fecha_registro' => now()]);

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.indicadores'));

        $response->assertInertia(function ($page) use ($recomendacionMedica, $recomendacionOcupacional, $recomendacionHabitos, $concepto) {
            $props = $page->toArray()['props'];

            $conceptos = collect($props['porConceptoAptitud'])->keyBy('nombre');
            \PHPUnit\Framework\Assert::assertSame(2, $conceptos[$concepto->nombre]['cantidad']);

            $medicas = collect($props['recomendacionesMedicas'])->keyBy('nombre');
            \PHPUnit\Framework\Assert::assertSame(1, $medicas[$recomendacionMedica->nombre]['cantidad']);

            $ocupacionales = collect($props['recomendacionesOcupacionales'])->keyBy('nombre');
            \PHPUnit\Framework\Assert::assertSame(1, $ocupacionales[$recomendacionOcupacional->nombre]['cantidad']);

            $habitos = collect($props['habitosEstilosVida'])->keyBy('nombre');
            \PHPUnit\Framework\Assert::assertSame(1, $habitos[$recomendacionHabitos->nombre]['cantidad']);

            return true;
        });
    }

    public function test_estado_de_evaluaciones_incluye_ambos_vocabularios_ingreso_periodico_y_egreso(): void
    {
        $user = $this->seguridadUser();
        $service = new EvaluacionMedicaService();

        $service->crear($this->colaborador(), 'ingreso'); // sin_iniciar
        $service->crearEgreso($this->colaborador()); // pendiente

        $response = $this->actingAs($user)->get(route('seguridad.examenes-medicos.indicadores'));

        $response->assertInertia(function ($page) {
            $estados = collect($page->toArray()['props']['estadoExamen'])->keyBy('valor');
            \PHPUnit\Framework\Assert::assertSame(2, $estados['sin_iniciar']['cantidad']);
            \PHPUnit\Framework\Assert::assertSame(0, $estados['terminada']['cantidad']);

            return true;
        });
    }
}
