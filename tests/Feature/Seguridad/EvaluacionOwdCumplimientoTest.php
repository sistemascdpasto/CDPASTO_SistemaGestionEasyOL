<?php

namespace Tests\Feature\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwd;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use App\Services\Seguridad\EvaluacionOwdCumplimientoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EvaluacionOwdCumplimientoTest extends TestCase
{
    use RefreshDatabase;

    private function colaborador(): Colaborador
    {
        return Colaborador::create([
            'cedula' => '900555666', 'nombres' => 'Victor', 'apellidos' => 'Martinez', 'estado_registro' => 'completo',
            'codigo_qr_skap' => 'EVALUADO1',
        ]);
    }

    private function evaluacion(Colaborador $colaborador, Carbon $fecha): EvaluacionOwd
    {
        return EvaluacionOwd::create([
            'qr_safety' => $colaborador->codigo_qr_skap,
            'colaborador_id' => $colaborador->id,
            'fecha_evaluacion' => $fecha,
        ]);
    }

    public function test_calcula_el_porcentaje_excluyendo_not_applicable_y_determina_cumple(): void
    {
        $colaborador = $this->colaborador();
        $hasta = Carbon::create(2026, 8, 14);

        $evaluacion = $this->evaluacion($colaborador, $hasta->copy()->subDays(5));

        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacion->id, 'puntuacion' => 'OK', 'tarea' => 'A']);
        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacion->id, 'puntuacion' => 'OK', 'tarea' => 'B']);
        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacion->id, 'puntuacion' => 'No OK', 'tarea' => 'C']);
        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacion->id, 'puntuacion' => 'Not Applicable', 'tarea' => 'D']);

        $resultado = (new EvaluacionOwdCumplimientoService())->calcular($colaborador, $hasta);

        // 2 OK de 3 aplicables (se excluye la "Not Applicable" del denominador).
        $this->assertSame(3, $resultado['total_preguntas']);
        $this->assertSame(2, $resultado['preguntas_ok']);
        $this->assertSame(1, $resultado['preguntas_no_conformes']);
        $this->assertEqualsWithDelta(66.7, $resultado['porcentaje'], 0.1);
        $this->assertFalse($resultado['cumple']);
        $this->assertCount(1, $resultado['preguntas_incumplidas']);
    }

    public function test_cumple_100_por_ciento_cuando_todas_las_aplicables_estan_ok(): void
    {
        $colaborador = $this->colaborador();
        $hasta = Carbon::create(2026, 8, 14);

        $evaluacion = $this->evaluacion($colaborador, $hasta->copy()->subDays(10));

        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacion->id, 'puntuacion' => 'OK', 'tarea' => 'A']);
        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacion->id, 'puntuacion' => 'Not Applicable', 'tarea' => 'B']);

        $resultado = (new EvaluacionOwdCumplimientoService())->calcular($colaborador, $hasta);

        $this->assertSame(100.0, $resultado['porcentaje']);
        $this->assertTrue($resultado['cumple']);
        $this->assertSame(0, $resultado['faltantes']);
    }

    public function test_solo_considera_la_ventana_movil_de_3_meses(): void
    {
        $colaborador = $this->colaborador();
        $hasta = Carbon::create(2026, 8, 14);

        // Evaluación fuera de la ventana (hace 5 meses) no debe contar.
        $evaluacionVieja = $this->evaluacion($colaborador, $hasta->copy()->subMonths(5));
        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacionVieja->id, 'puntuacion' => 'No OK', 'tarea' => 'Vieja']);

        $evaluacionReciente = $this->evaluacion($colaborador, $hasta->copy()->subDays(2));
        EvaluacionOwdPregunta::create(['evaluacion_owd_id' => $evaluacionReciente->id, 'puntuacion' => 'OK', 'tarea' => 'Reciente']);

        $resultado = (new EvaluacionOwdCumplimientoService())->calcular($colaborador, $hasta);

        $this->assertSame(1, $resultado['total_preguntas']);
        $this->assertSame(100.0, $resultado['porcentaje']);
    }
}
