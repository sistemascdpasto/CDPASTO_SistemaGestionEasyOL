<?php

namespace App\Http\Controllers\Reparto;

use App\Http\Controllers\Controller;
use App\Models\Reparto\EventosTripulacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndicadoresAdherenciaController extends Controller
{
    public function index(Request $request): Response
    {
        // ── Filtros ───────────────────────────────────────────────────────────
        $fechaDesdeFiltro = $request->input('fecha_desde', '');
        $fechaHastaFiltro = $request->input('fecha_hasta', '');

        $minFecha = \App\Models\Reparto\EventosTripulacion::min('fecha');
        $maxFecha = \App\Models\Reparto\EventosTripulacion::max('fecha');

        $fechaDesde = $request->filled('fecha_desde')
            ? $fechaDesdeFiltro
            : ($minFecha ?: Carbon::now()->subYear()->format('Y-m-d'));

        $fechaHasta = $request->filled('fecha_hasta')
            ? $fechaHastaFiltro
            : ($maxFecha ?: Carbon::now()->format('Y-m-d'));

        $filterPlaca = $request->filled('placa')
            ? strtoupper(trim($request->input('placa')))
            : null;

        $filterDocumento = $request->filled('documento')
            ? trim($request->input('documento'))
            : null;

        // ── Query base ────────────────────────────────────────────────────────
        $query = EventosTripulacion::query()
            ->whereDate('fecha', '>=', $fechaDesde)
            ->whereDate('fecha', '<=', $fechaHasta)
            ->where(function ($q) {
                $q->whereNotNull('adherencia_checklist_pre')
                  ->orWhereNotNull('adherencia_checklist_post');
            });

        if ($filterPlaca) {
            $query->where('placa', 'LIKE', $filterPlaca . '%');
        }
        if ($filterDocumento) {
            $query->where('documento', 'LIKE', $filterDocumento . '%');
        }

        $rows = $query
            ->select([
                'id', 'fecha', 'placa', 'documento', 'nombre', 'cargo',
                'adherencia_checklist_pre', 'adherencia_checklist_post',
            ])
            ->orderBy('fecha')
            ->get();

        // ── Distribución en rangos (pasteles) ─────────────────────────────────
        $categorizar = fn (?float $p): string => match(true) {
            $p === null  => 'Sin dato',
            $p >= 90     => '≥ 90% (Óptimo)',
            $p >= 70     => '70–89% (Aceptable)',
            default      => '< 70% (Crítico)',
        };

        $distPre  = ['≥ 90% (Óptimo)' => 0, '70–89% (Aceptable)' => 0, '< 70% (Crítico)' => 0, 'Sin dato' => 0];
        $distPost = ['≥ 90% (Óptimo)' => 0, '70–89% (Aceptable)' => 0, '< 70% (Crítico)' => 0, 'Sin dato' => 0];

        foreach ($rows as $row) {
            $distPre[$categorizar($row->adherencia_checklist_pre !== null ? (float)$row->adherencia_checklist_pre : null)]++;
            $distPost[$categorizar($row->adherencia_checklist_post !== null ? (float)$row->adherencia_checklist_post : null)]++;
        }

        // ── Promedios generales ───────────────────────────────────────────────
        $promPre  = $rows->whereNotNull('adherencia_checklist_pre')->avg('adherencia_checklist_pre');
        $promPost = $rows->whereNotNull('adherencia_checklist_post')->avg('adherencia_checklist_post');

        // ── Top 10 menor adherencia Pre ───────────────────────────────────────
        $topBajaPre = $rows
            ->whereNotNull('adherencia_checklist_pre')
            ->sortBy('adherencia_checklist_pre')
            ->take(10)
            ->map(fn ($r) => [
                'nombre' => $r->nombre ?? $r->documento,
                'cedula' => $r->documento,
                'placa'  => $r->placa,
                'fecha'  => Carbon::parse($r->fecha)->format('d/m/Y'),
                'pre'    => round((float)$r->adherencia_checklist_pre, 1),
                'post'   => $r->adherencia_checklist_post !== null
                                ? round((float)$r->adherencia_checklist_post, 1) : null,
            ])
            ->values();

        // ── Tendencia diaria ──────────────────────────────────────────────────
        $porFecha = $rows
            ->groupBy(fn ($r) => Carbon::parse($r->fecha)->format('Y-m-d'))
            ->map(fn ($g, $dia) => [
                'fecha'    => Carbon::parse($dia)->format('d/m'),
                'promPre'  => round($g->whereNotNull('adherencia_checklist_pre')->avg('adherencia_checklist_pre') ?? 0, 1),
                'promPost' => round($g->whereNotNull('adherencia_checklist_post')->avg('adherencia_checklist_post') ?? 0, 1),
            ])
            ->sortKeys()
            ->values();

        // ── Heatmap: días × placas ────────────────────────────────────────────
        $placasHeatmap = $rows->pluck('placa')->filter()->unique()->sort()->values()->toArray();
        $diasHeatmap   = $rows->map(fn ($r) => (int) Carbon::parse($r->fecha)->format('j'))
            ->unique()->sort()->values()->toArray();

        $celdasRaw = [];
        foreach ($rows as $row) {
            $dia   = (int) Carbon::parse($row->fecha)->format('j');
            $placa = $row->placa ?? '';
            $key   = $dia . '|' . $placa;
            if (!isset($celdasRaw[$key])) $celdasRaw[$key] = [];
            $celdasRaw[$key][] = [
                'nombre' => $row->nombre ?? ($row->documento ?? '?'),
                'pre'    => $row->adherencia_checklist_pre  !== null ? round((float)$row->adherencia_checklist_pre,  1) : null,
                'post'   => $row->adherencia_checklist_post !== null ? round((float)$row->adherencia_checklist_post, 1) : null,
            ];
        }

        $celdas = [];
        foreach ($celdasRaw as $key => $tripulantes) {
            $todasOk = true; $sumPre = 0; $cntPre = 0; $sumPost = 0; $cntPost = 0;
            foreach ($tripulantes as $t) {
                if ($t['pre']  !== null) { $sumPre  += $t['pre'];  $cntPre++; }
                if ($t['post'] !== null) { $sumPost += $t['post']; $cntPost++; }
                if (($t['pre'] !== null && $t['pre'] < 100) || ($t['post'] !== null && $t['post'] < 100)) $todasOk = false;
            }
            $celdas[$key] = [
                'estado'      => $todasOk ? 'ok' : 'critico',
                'promPre'     => $cntPre  > 0 ? round($sumPre  / $cntPre,  1) : null,
                'promPost'    => $cntPost > 0 ? round($sumPost / $cntPost, 1) : null,
                'tripulantes' => $tripulantes,
            ];
        }

        $totalCeldas          = count($placasHeatmap) * count($diasHeatmap);
        $celdasOk             = collect($celdas)->where('estado', 'ok')->count();
        $celdasCritico        = collect($celdas)->where('estado', 'critico')->count();
        $celdasRegistradas    = $celdasOk + $celdasCritico;
        $pctDiligenciamiento  = $totalCeldas > 0 ? round(($celdasRegistradas / $totalCeldas) * 100, 1) : 0;

        // ── KPIs extra ────────────────────────────────────────────────────────
        $totalTripulantes = $rows->pluck('documento')->filter()->unique()->count();

        // Top 5 placas con mejor cumplimiento promedio Pre
        $topMejor = $rows->whereNotNull('adherencia_checklist_pre')
            ->groupBy('placa')
            ->map(fn ($g, $p) => [
                'placa' => $p,
                'prom'  => round($g->avg('adherencia_checklist_pre'), 1),
                'total' => $g->count(),
            ])
            ->sortByDesc('prom')
            ->take(5)
            ->values();

        // Top 5 placas con peor cumplimiento (atención requerida)
        $topCriticos = $rows->whereNotNull('adherencia_checklist_pre')
            ->groupBy('placa')
            ->map(fn ($g, $p) => [
                'placa'    => $p,
                'prom'     => round($g->avg('adherencia_checklist_pre'), 1),
                'criticos' => $g->filter(fn ($r) => (float)$r->adherencia_checklist_pre < 70)->count(),
            ])
            ->sortBy('prom')
            ->take(5)
            ->values();

        // Placas disponibles para filtro
        $placasFiltro = EventosTripulacion::whereNotNull('placa')
            ->distinct()->orderBy('placa')->pluck('placa');

        return Inertia::render('reparto/indicadores-adherencia/index', [
            'distPre'    => $distPre,
            'distPost'   => $distPost,
            'promPre'    => $promPre  !== null ? round((float)$promPre,  1) : null,
            'promPost'   => $promPost !== null ? round((float)$promPost, 1) : null,
            'total'      => $rows->count(),
            'porFecha'   => $porFecha,
            'topBajaPre' => $topBajaPre,
            'heatmap' => [
                'placas'              => $placasHeatmap,
                'dias'                => $diasHeatmap,
                'celdas'              => $celdas,
                'totalCeldas'         => $totalCeldas,
                'celdasOk'            => $celdasOk,
                'celdasCritico'       => $celdasCritico,
                'celdasVacio'         => $totalCeldas - $celdasRegistradas,
                'pctDiligenciamiento' => $pctDiligenciamiento,
            ],
            'kpis' => [
                'totalTripulantes' => $totalTripulantes,
                'celdasCritico'    => $celdasCritico,
                'celdasVacio'      => $totalCeldas - $celdasRegistradas,
            ],
            'topMejor'    => $topMejor,
            'topCriticos' => $topCriticos,
            'placas'      => $placasFiltro,
            'filters'     => [
                'fecha_desde' => $fechaDesdeFiltro,
                'fecha_hasta' => $fechaHastaFiltro,
                'placa'       => $filterPlaca ?? '',
                'documento'   => $filterDocumento ?? '',
            ],
        ]);
    }
}
