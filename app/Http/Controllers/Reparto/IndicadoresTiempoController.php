<?php

namespace App\Http\Controllers\Reparto;

use App\Http\Controllers\Controller;
use App\Models\Reparto\EventosTripulacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndicadoresTiempoController extends Controller
{
    /** Meta interna de adherencia al tiempo */
    const META = 95.0;

    public function index(Request $request): Response
    {
        // ── Filtros ───────────────────────────────────────────────────────────
        $fechaDesdeFiltro = $request->input('fecha_desde', '');
        $fechaHastaFiltro = $request->input('fecha_hasta', '');
        $filterCargo  = $request->input('cargo', '');
        $filterPlacas = array_filter(array_map('strtoupper', (array) $request->input('placas', [])));

        // Rango por defecto: min/max real de la tabla
        $minFecha = EventosTripulacion::whereNotNull('adherencia_tiempo')->min('fecha');
        $maxFecha = EventosTripulacion::whereNotNull('adherencia_tiempo')->max('fecha');

        $fechaDesde = $fechaDesdeFiltro ?: ($minFecha ?: Carbon::now()->subMonth()->format('Y-m-d'));
        $fechaHasta = $fechaHastaFiltro ?: ($maxFecha ?: Carbon::now()->format('Y-m-d'));

        // ── Query base ────────────────────────────────────────────────────────
        $query = EventosTripulacion::query()
            ->whereNotNull('adherencia_tiempo')
            ->whereDate('fecha', '>=', $fechaDesde)
            ->whereDate('fecha', '<=', $fechaHasta);

        if ($filterCargo) {
            $query->where('cargo', $filterCargo);
        }
        if (!empty($filterPlacas)) {
            $query->whereIn('placa', $filterPlacas);
        }

        $rows = $query
            ->select(['fecha', 'placa', 'documento', 'nombre', 'rr', 'rr_pasto', 'cargo', 'adherencia_tiempo'])
            ->orderBy('fecha')
            ->get();

        $total = $rows->count();

        if ($total === 0) {
            return $this->emptyResponse($request, $fechaDesdeFiltro, $fechaHastaFiltro, $filterCargo, $filterPlacas);
        }

        // ── KPIs principales ──────────────────────────────────────────────────
        $promedio     = round($rows->avg('adherencia_tiempo'), 1);
        $bajoCritico  = $rows->filter(fn ($r) => (float)$r->adherencia_tiempo < 80)->count();
        $ceros        = $rows->filter(fn ($r) => (float)$r->adherencia_tiempo == 0)->count();
        $enMeta       = $rows->filter(fn ($r) => (float)$r->adherencia_tiempo >= self::META)->count();

        $pctBajoCritico = $total > 0 ? round(($bajoCritico / $total) * 100, 1) : 0;
        $pctEnMeta      = $total > 0 ? round(($enMeta      / $total) * 100, 1) : 0;
        $gap            = round(self::META - $promedio, 1);

        // ── Tendencia diaria (sparkline + gráfica diaria) ─────────────────────
        $porDia = $rows
            ->groupBy(fn ($r) => Carbon::parse($r->fecha)->format('Y-m-d'))
            ->map(fn ($g, $dia) => [
                'fecha'        => Carbon::parse($dia)->format('d/m'),
                'fecha_full'   => $dia,
                'promedio'     => round($g->avg('adherencia_tiempo'), 1),
                'bajo_critico' => $g->filter(fn ($r) => (float)$r->adherencia_tiempo < 80)->count(),
                'ceros'        => $g->filter(fn ($r) => (float)$r->adherencia_tiempo == 0)->count(),
                'total'        => $g->count(),
                // Placas únicas del día
                'placas'       => $g->pluck('placa')->filter()->unique()->sort()->values()->toArray(),
                // Personas del día (nombre + promedio del día)
                'personas'     => $g->groupBy('documento')
                    ->map(fn ($gp, $doc) => [
                        'nombre'   => $gp->first(fn ($r) => !empty($r->nombre))?->nombre
                                      ?? $gp->first(fn ($r) => !empty($r->rr_pasto))?->rr_pasto
                                      ?? $doc,
                        'placa'    => $gp->first()->placa ?? '',
                        'promedio' => round($gp->avg('adherencia_tiempo'), 1),
                    ])
                    ->sortBy('promedio')
                    ->take(10)
                    ->values()
                    ->toArray(),
            ])
            ->sortKeys()
            ->values();

        // ── Patrón por día de semana ──────────────────────────────────────────
        $diasSemana    = ['Lun' => [], 'Mar' => [], 'Mié' => [], 'Jue' => [], 'Vie' => [], 'Sáb' => [], 'Dom' => []];
        $diasSemanaGrp = ['Lun' => collect(), 'Mar' => collect(), 'Mié' => collect(), 'Jue' => collect(), 'Vie' => collect(), 'Sáb' => collect(), 'Dom' => collect()];
        $mapDow        = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
        foreach ($rows as $row) {
            $dow = Carbon::parse($row->fecha)->dayOfWeekIso;
            $diasSemana[$mapDow[$dow]][]    = (float)$row->adherencia_tiempo;
            $diasSemanaGrp[$mapDow[$dow]][] = $row;
        }
        $patronDow = collect($diasSemana)->map(function ($vals, $dia) use ($diasSemanaGrp) {
            $grp = collect($diasSemanaGrp[$dia]);
            return [
                'dia'          => $dia,
                'promedio'     => count($vals) > 0 ? round(array_sum($vals) / count($vals), 1) : null,
                'total'        => count($vals),
                'bajo_critico' => count(array_filter($vals, fn ($v) => $v < 80)),
                // Placas únicas de ese día de semana
                'placas'       => $grp->pluck('placa')->filter()->unique()->sort()->take(8)->values()->toArray(),
                // Top 5 personas con peor promedio en ese día de semana
                'personas'     => $grp->groupBy('documento')
                    ->map(fn ($gp, $doc) => [
                        'nombre'   => $gp->first(fn ($r) => !empty($r->nombre))?->nombre
                                      ?? $gp->first(fn ($r) => !empty($r->rr_pasto))?->rr_pasto
                                      ?? $doc,
                        'placa'    => $gp->first()->placa ?? '',
                        'promedio' => round($gp->avg('adherencia_tiempo'), 1),
                    ])
                    ->sortBy('promedio')
                    ->take(5)
                    ->values()
                    ->toArray(),
            ];
        })->values();

        // ── Distribución por rangos (histograma / donut) ──────────────────────
        $bandas = [
            'Crítico (0–49%)'  => 0,
            'Bajo (50–79%)'    => 0,
            'Medio (80–89%)'   => 0,
            'Bueno (90–94%)'   => 0,
            'Óptimo (95–100%)' => 0,
        ];
        // Histograma: rangos de 10 en 10
        $histogramaRaw = array_fill(0, 10, 0); // índices 0..9 → 0-9%, 10-19%, ..., 90-100%
        foreach ($rows as $row) {
            $v = (float)$row->adherencia_tiempo;
            if ($v < 50)       $bandas['Crítico (0–49%)']++;
            elseif ($v < 80)   $bandas['Bajo (50–79%)']++;
            elseif ($v < 90)   $bandas['Medio (80–89%)']++;
            elseif ($v < 95)   $bandas['Bueno (90–94%)']++;
            else               $bandas['Óptimo (95–100%)']++;

            $bucket = min(9, (int)floor($v / 10));
            $histogramaRaw[$bucket]++;
        }
        $histograma = array_map(fn ($i) => [
            'rango'  => ($i * 10) . '–' . ($i * 10 + 9) . '%',
            'inicio' => $i * 10,
            'total'  => $histogramaRaw[$i],
        ], array_keys($histogramaRaw));

        // ── Por persona ───────────────────────────────────────────────────────
        $porPersona = $rows
            ->whereNotNull('documento')
            ->groupBy('documento')
            ->map(fn ($g, $doc) => [
                'documento'    => $doc,
                // Nombre: campo nombre → rr_pasto → rr → documento
                'nombre'       => $g->first(fn ($r) => !empty($r->nombre))?->nombre
                                  ?? $g->first(fn ($r) => !empty($r->rr_pasto))?->rr_pasto
                                  ?? $g->first(fn ($r) => !empty($r->rr))?->rr
                                  ?? $doc,
                'cargo'        => $g->first()->cargo ?? '',
                // Placa más frecuente de la persona en el período
                'placa'        => $g->groupBy('placa')
                                    ->map(fn ($gp) => $gp->count())
                                    ->sortDesc()
                                    ->keys()
                                    ->first() ?? '',
                'dias'         => $g->count(),
                'promedio'     => round($g->avg('adherencia_tiempo'), 1),
                'bajo_critico' => $g->filter(fn ($r) => (float)$r->adherencia_tiempo < 80)->count(),
                'ceros'        => $g->filter(fn ($r) => (float)$r->adherencia_tiempo == 0)->count(),
                'en_meta'      => $g->filter(fn ($r) => (float)$r->adherencia_tiempo >= self::META)->count(),
            ])
            ->filter(fn ($p) => $p['dias'] >= 1)
            ->values();

        // Ranking bottom/top (solo personas con 10+ días)
        $conSufDias = $porPersona->filter(fn ($p) => $p['dias'] >= 10)->values();
        $rankBottom = $conSufDias->sortBy('promedio')->take(12)->values();
        $rankTop    = $conSufDias->sortByDesc('promedio')->take(12)->values();

        // Pareto: personas con más jornadas bajo 80% (top 20)
        $pareto = $porPersona
            ->sortByDesc('bajo_critico')
            ->take(20)
            ->values()
            ->map(function ($p, $i) use ($porPersona) {
                static $acum = 0;
                $totalBajo = $porPersona->sum('bajo_critico');
                $acum += $p['bajo_critico'];
                return array_merge($p, [
                    'pct_acumulado' => $totalBajo > 0 ? round(($acum / $totalBajo) * 100, 1) : 0,
                ]);
            });

        // Scatter: días vs promedio (tamaño = jornadas en 0%)
        $scatter = $porPersona
            ->filter(fn ($p) => $p['dias'] >= 5)
            ->map(fn ($p) => [
                'nombre'       => $p['nombre'],
                'dias'         => $p['dias'],
                'promedio'     => $p['promedio'],
                'ceros'        => $p['ceros'],
                'cargo'        => $p['cargo'],
            ])
            ->values();

        // ── Por cargo (con detalle de personas, placas y fechas) ──────────────
        $porCargo = $rows
            ->whereNotNull('cargo')
            ->groupBy('cargo')
            ->map(fn ($g, $cargo) => [
                'cargo'        => $cargo,
                'promedio'     => round($g->avg('adherencia_tiempo'), 1),
                'total'        => $g->count(),
                'bajo_critico' => $g->filter(fn ($r) => (float)$r->adherencia_tiempo < 80)->count(),
                'pct_bajo'     => $g->count() > 0
                    ? round(($g->filter(fn ($r) => (float)$r->adherencia_tiempo < 80)->count() / $g->count()) * 100, 1)
                    : 0,
                // Placas únicas del cargo
                'placas'       => $g->pluck('placa')->filter()->unique()->sort()->values()->toArray(),
                // Personas del cargo con sus métricas
                'personas'     => $g->groupBy('documento')
                    ->map(fn ($gp, $doc) => [
                        'nombre'       => $gp->first(fn ($r) => !empty($r->nombre))?->nombre
                                          ?? $gp->first(fn ($r) => !empty($r->rr_pasto))?->rr_pasto
                                          ?? $doc,
                        'placa'        => $gp->groupBy('placa')
                                            ->map(fn ($gpp) => $gpp->count())
                                            ->sortDesc()->keys()->first() ?? '',
                        'promedio'     => round($gp->avg('adherencia_tiempo'), 1),
                        'dias'         => $gp->count(),
                        'bajo_critico' => $gp->filter(fn ($r) => (float)$r->adherencia_tiempo < 80)->count(),
                        'fecha_min'    => Carbon::parse($gp->min('fecha'))->format('d/m/Y'),
                        'fecha_max'    => Carbon::parse($gp->max('fecha'))->format('d/m/Y'),
                    ])
                    ->sortBy('promedio')
                    ->take(20)
                    ->values()
                    ->toArray(),
                'fecha_min'    => Carbon::parse($g->min('fecha'))->format('d/m/Y'),
                'fecha_max'    => Carbon::parse($g->max('fecha'))->format('d/m/Y'),
            ])
            ->sortByDesc('total')
            ->values();

        // ── Por placa ─────────────────────────────────────────────────────────
        $porPlaca = $rows
            ->whereNotNull('placa')
            ->groupBy('placa')
            ->map(fn ($g, $placa) => [
                'placa'    => $placa,
                'promedio' => round($g->avg('adherencia_tiempo'), 1),
                'total'    => $g->count(),
            ])
            ->filter(fn ($p) => $p['total'] >= 10)
            ->sortBy('promedio')
            ->values();

        // ── Heatmap persona×día ───────────────────────────────────────────────
        // Solo personas con 15+ días, ordenadas de mejor a peor promedio
        $personasHeat = $porPersona
            ->filter(fn ($p) => $p['dias'] >= 15)
            ->sortByDesc('promedio')
            ->pluck('documento')
            ->values()
            ->toArray();

        $celdas = [];
        foreach ($rows as $row) {
            if (!$row->documento || !in_array($row->documento, $personasHeat)) continue;
            $dia = Carbon::parse($row->fecha)->format('Y-m-d');
            $celdas[$row->documento . '|' . $dia] = round((float)$row->adherencia_tiempo, 1);
        }

        $diasHeat = $porDia->pluck('fecha_full')->values()->toArray();
        $nombresHeat = collect($personasHeat)
            ->map(fn ($doc) => [
                'documento' => $doc,
                'nombre'    => $porPersona->firstWhere('documento', $doc)['nombre'] ?? $doc,
            ])
            ->values();

        // ── Placas disponibles para el filtro ────────────────────────────────
        $cargos = EventosTripulacion::whereNotNull('cargo')
            ->whereNotNull('adherencia_tiempo')
            ->distinct()->orderBy('cargo')->pluck('cargo');

        $todasPlacas = EventosTripulacion::whereNotNull('placa')
            ->whereNotNull('adherencia_tiempo')
            ->distinct()->orderBy('placa')->pluck('placa');

        return Inertia::render('reparto/indicadores-tiempo/index', [
            'kpis' => [
                'promedio'         => $promedio,
                'total'            => $total,
                'bajo_critico'     => $bajoCritico,
                'pct_bajo_critico' => $pctBajoCritico,
                'ceros'            => $ceros,
                'en_meta'          => $enMeta,
                'pct_en_meta'      => $pctEnMeta,
                'gap'              => $gap,
                'meta'             => self::META,
                'personas'         => $porPersona->count(),
                'placas'           => $rows->pluck('placa')->filter()->unique()->count(),
                'dias_con_datos'   => $porDia->count(),
            ],
            'sparkline'  => $porDia->pluck('promedio')->values(),
            'por_dia'    => $porDia,
            'patron_dow' => $patronDow,
            'histograma' => $histograma,
            'bandas'     => $bandas,
            'rank_bottom'=> $rankBottom,
            'rank_top'   => $rankTop,
            'pareto'     => $pareto,
            'scatter'    => $scatter,
            'por_cargo'  => $porCargo,
            'por_placa'  => $porPlaca,
            'heatmap' => [
                'personas' => $nombresHeat,
                'dias'     => $diasHeat,
                'celdas'   => $celdas,
            ],
            'cargos'      => $cargos,
            'todasPlacas' => $todasPlacas,
            'filters' => [
                'fecha_desde' => $fechaDesdeFiltro,
                'fecha_hasta' => $fechaHastaFiltro,
                'cargo'       => $filterCargo,
                'placas'      => array_values($filterPlacas),
            ],
        ]);
    }

    private function emptyResponse(Request $request, string $fd, string $fh, string $cargo, array $placas): Response
    {
        $cargos = EventosTripulacion::whereNotNull('cargo')
            ->whereNotNull('adherencia_tiempo')
            ->distinct()->orderBy('cargo')->pluck('cargo');

        $todasPlacas = EventosTripulacion::whereNotNull('placa')
            ->whereNotNull('adherencia_tiempo')
            ->distinct()->orderBy('placa')->pluck('placa');

        return Inertia::render('reparto/indicadores-tiempo/index', [
            'kpis'       => ['promedio' => null, 'total' => 0, 'bajo_critico' => 0,
                             'pct_bajo_critico' => 0, 'ceros' => 0, 'en_meta' => 0,
                             'pct_en_meta' => 0, 'gap' => null, 'meta' => self::META,
                             'personas' => 0, 'placas' => 0, 'dias_con_datos' => 0],
            'sparkline'  => [],
            'por_dia'    => [],
            'patron_dow' => [],
            'histograma' => [],
            'bandas'     => [],
            'rank_bottom'=> [],
            'rank_top'   => [],
            'pareto'     => [],
            'scatter'    => [],
            'por_cargo'  => [],
            'por_placa'  => [],
            'heatmap'    => ['personas' => [], 'dias' => [], 'celdas' => []],
            'cargos'      => $cargos,
            'todasPlacas' => $todasPlacas,
            'filters'    => ['fecha_desde' => $fd, 'fecha_hasta' => $fh, 'cargo' => $cargo, 'placas' => $placas],
        ]);
    }
}
