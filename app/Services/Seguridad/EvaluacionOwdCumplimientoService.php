<?php

namespace App\Services\Seguridad;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EvaluacionOwdPregunta;
use Illuminate\Support\Carbon;

/**
 * Cálculo del cumplimiento OWD por colaborador (HU-034/035): ventana móvil
 * de los últimos 3 meses, meta del 100%. Se centraliza en un solo servicio
 * porque lo usan tres pantallas distintas (indicadores generales, detalle
 * por colaborador HU-037, y evolución histórica HU-040) y todas deben usar
 * exactamente la misma regla de cálculo.
 */
class EvaluacionOwdCumplimientoService
{
    /**
     * @return array{
     *     periodo_desde: string,
     *     periodo_hasta: string,
     *     total_preguntas: int,
     *     preguntas_ok: int,
     *     preguntas_no_conformes: int,
     *     porcentaje: float,
     *     cumple: bool,
     *     faltantes: int,
     *     preguntas_incumplidas: \Illuminate\Support\Collection<int, EvaluacionOwdPregunta>,
     * }
     */
    public function calcular(Colaborador $colaborador, ?Carbon $hasta = null): array
    {
        $hasta = ($hasta ?? now())->endOfDay();
        $desde = $hasta->copy()->subMonths(3)->startOfDay();

        $preguntas = EvaluacionOwdPregunta::query()
            ->with('evaluacionOwd:id,colaborador_id,fecha_evaluacion,pillar')
            ->whereHas('evaluacionOwd', function ($query) use ($colaborador, $desde, $hasta) {
                $query->where('colaborador_id', $colaborador->id)
                    ->whereBetween('fecha_evaluacion', [$desde, $hasta]);
            })
            ->get();

        // "Not Applicable" no cuenta ni en el numerador ni en el denominador
        // (HU-034, requisito 6).
        $aplicables = $preguntas->filter(fn (EvaluacionOwdPregunta $pregunta) => $pregunta->puntuacion !== 'Not Applicable');
        $totalAplicables = $aplicables->count();
        $ok = $aplicables->where('puntuacion', 'OK')->count();
        $noConformes = $totalAplicables - $ok;
        $porcentaje = $totalAplicables > 0 ? round(($ok / $totalAplicables) * 100, 1) : 0.0;

        return [
            'periodo_desde' => $desde->toDateString(),
            'periodo_hasta' => $hasta->toDateString(),
            'total_preguntas' => $totalAplicables,
            'preguntas_ok' => $ok,
            'preguntas_no_conformes' => $noConformes,
            'porcentaje' => $porcentaje,
            'cumple' => $totalAplicables > 0 && $porcentaje >= 100.0,
            'faltantes' => max(0, $totalAplicables - $ok),
            'preguntas_incumplidas' => $aplicables->where('puntuacion', '!=', 'OK')->values(),
        ];
    }

    /**
     * Evolución del indicador en los últimos `$meses` cierres de mes
     * (HU-040), reutilizando `calcular()` para cada corte.
     *
     * @return array<int, array{periodo: string, porcentaje: float, cumple: bool, total_preguntas: int}>
     */
    public function evolucion(Colaborador $colaborador, int $meses = 6): array
    {
        $evolucion = [];
        $cursor = now()->endOfMonth();

        for ($i = 0; $i < $meses; $i++) {
            $resultado = $this->calcular($colaborador, $cursor->copy());

            $evolucion[] = [
                'periodo' => $cursor->format('Y-m'),
                'porcentaje' => $resultado['porcentaje'],
                'cumple' => $resultado['cumple'],
                'total_preguntas' => $resultado['total_preguntas'],
            ];

            $cursor->subMonth()->endOfMonth();
        }

        return array_reverse($evolucion);
    }
}
