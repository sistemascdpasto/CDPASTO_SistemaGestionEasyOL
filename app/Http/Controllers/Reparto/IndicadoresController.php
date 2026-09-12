<?php

namespace App\Http\Controllers\Reparto;

use App\Http\Controllers\Controller;
use App\Models\Reparto\AlertaVelocidadCurva;
use App\Models\Reparto\EventosTripulacion;
use App\Models\Seguridad\Colaborador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class IndicadoresController extends Controller
{
    public function index(Request $request): Response
    {
        // Los inputs quedan vacíos por defecto — solo la query usa el rango de 30 días
        $fechaDesdeFiltro = $request->input('fecha_desde', '');
        $fechaHastaFiltro = $request->input('fecha_hasta', '');

        // Para la query usamos el rango; si no hay filtro aplicamos los últimos 30 días
        $fechaDesde = $request->filled('fecha_desde')
            ? $fechaDesdeFiltro
            : Carbon::now()->subDays(30)->format('Y-m-d');

        $fechaHasta = $request->filled('fecha_hasta')
            ? $fechaHastaFiltro
            : Carbon::now()->format('Y-m-d');

        // ── Filtro por colaborador(es) (cedulas[]) ────────────────────────────────
        // Si vienen cedulas → buscamos TODAS las placas de TODOS esos conductores
        // en eventos_tripulacion y las usamos como filtro de placas.
        $cedulas = collect($request->input('cedulas', []))
            ->map(fn ($c) => trim($c))
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Fallback: parámetro legacy singular
        if (empty($cedulas) && $request->filled('cedula')) {
            $cedulas = [trim($request->input('cedula'))];
        }

        $hayFiltroColaborador = !empty($cedulas);

        $placasPorColaborador = [];
        $colaboradoresSeleccionados = [];

        if ($hayFiltroColaborador) {
            // Todas las placas que manejaron esos conductores
            $placasPorColaborador = EventosTripulacion::whereIn('documento', $cedulas)
                ->distinct()
                ->pluck('placa')
                ->map(fn ($p) => strtoupper(trim($p)))
                ->filter()
                ->unique()
                ->values()
                ->toArray();

            $cols = Colaborador::whereIn('cedula', $cedulas)
                ->orderBy('nombres')
                ->get(['cedula', 'nombres', 'apellidos']);

            $colaboradoresSeleccionados = $cols->map(fn ($c) => [
                'cedula' => $c->cedula,
                'nombre' => trim($c->nombres . ' ' . $c->apellidos),
            ])->values()->toArray();
        }

        // ── Filtro por placas manuales (solo aplica si no hay filtro por cedulas) ─
        $placasSeleccionadas = [];

        if (!$hayFiltroColaborador) {
            $placasSeleccionadas = collect($request->input('placas', []))
                ->map(fn ($p) => strtoupper(trim($p)))
                ->filter()->unique()->values()->toArray();

            if (empty($placasSeleccionadas) && $request->filled('placa')) {
                $placasSeleccionadas = [strtoupper(trim($request->input('placa')))];
            }
        } else {
            $placasSeleccionadas = $placasPorColaborador;
        }

        $hayFiltroPlaca = !empty($placasSeleccionadas);

        // ── Query base ────────────────────────────────────────────────────────
        $query = AlertaVelocidadCurva::query()
            ->whereNotNull('coordenada')
            ->whereNotNull('velocidad')
            ->whereDate('fecha', '>=', $fechaDesde)
            ->whereDate('fecha', '<=', $fechaHasta)
            ->when($hayFiltroPlaca, fn ($q) => $q->whereIn('nombre', $placasSeleccionadas));

        $alertas = $query
            ->orderByDesc('fecha')
            ->limit(1000)
            ->get(['id', 'fecha', 'hora', 'nombre', 'alerta', 'velocidad', 'coordenada', 'cantidad_eventos', 'regional', 'cd']);

        // ── Parsear coordenadas ───────────────────────────────────────────────
        $puntos = $alertas->map(function ($a) {
            $coords = explode(',', $a->coordenada ?? '');
            if (count($coords) < 2) return null;
            $lat = (float) trim($coords[0]);
            $lon = (float) trim($coords[1]);
            if ($lat === 0.0 && $lon === 0.0) return null;
            if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) return null;
            return [
                'id'         => $a->id,
                'fecha'      => $a->fecha?->format('d/m/Y') ?? '—',
                'hora'       => $a->hora ?? '—',
                'placa'      => $a->nombre ?? '—',
                'alerta'     => $a->alerta ?? '—',
                'velocidad'  => (float) $a->velocidad,
                'coordenada' => $a->coordenada,
                'eventos'    => $a->cantidad_eventos ?? 1,
                'regional'   => $a->regional ?? '—',
                'cd'         => $a->cd ?? '—',
                'lat'        => $lat,
                'lon'        => $lon,
            ];
        })->filter()->values();

        // ── Gráfica por día (desde eventos_tripulacion) ──────────────────────
        if (!$hayFiltroPlaca) {
            $diasQuery = EventosTripulacion::query()
                ->selectRaw('DATE(fecha) as dia, SUM(COALESCE(alertas_velocidad_curvas, 0)) as total')
                ->whereDate('fecha', '>=', $fechaDesde)
                ->whereDate('fecha', '<=', $fechaHasta)
                ->groupByRaw('DATE(fecha)')
                ->orderByRaw('DATE(fecha)')
                ->get();

            $labels   = $diasQuery->map(fn ($r) => Carbon::parse($r->dia)->format('d/m'))->toArray();
            $porFecha = [
                'labels' => $labels,
                'series' => [[
                    'placa'   => 'Total',
                    'valores' => $diasQuery->map(fn ($r) => (int) $r->total)->toArray(),
                ]],
            ];
        } else {
            $diasRaw = EventosTripulacion::query()
                ->selectRaw('placa as nombre, DATE(fecha) as dia, SUM(COALESCE(alertas_velocidad_curvas, 0)) as total')
                ->whereIn('placa', $placasSeleccionadas)
                ->whereDate('fecha', '>=', $fechaDesde)
                ->whereDate('fecha', '<=', $fechaHasta)
                ->groupByRaw('placa, DATE(fecha)')
                ->orderByRaw('DATE(fecha)')
                ->get();

            $allDias = $diasRaw->pluck('dia')->unique()->sort()->values();
            $labels  = $allDias->map(fn ($d) => Carbon::parse($d)->format('d/m'))->toArray();

            // Mapa placa → nombre del conductor (desde eventos_tripulacion)
            $conductorPorPlaca = EventosTripulacion::whereIn('placa', $placasSeleccionadas)
                ->whereNotNull('documento')
                ->whereNotNull('nombre')
                ->select('placa', 'nombre', 'documento')
                ->orderByDesc('fecha')
                ->get()
                ->groupBy('placa')
                ->map(fn ($rows) => $rows->first()->nombre); // nombre más reciente

            $series = collect($placasSeleccionadas)->map(function ($placa) use ($diasRaw, $allDias, $conductorPorPlaca) {
                $byDia = $diasRaw->where('nombre', $placa)->keyBy('dia');
                return [
                    'placa'     => $placa,
                    'conductor' => $conductorPorPlaca[$placa] ?? null,
                    'valores'   => $allDias->map(fn ($d) => (int) ($byDia[$d]->total ?? 0))->toArray(),
                ];
            })->values()->toArray();

            $porFecha = ['labels' => $labels, 'series' => $series];
        }

        // ── Top 10 placas (mes actual) (desde eventos_tripulacion) ────────────
        $mesActual  = now()->month;
        $anioActual = now()->year;
        $porPlaca   = EventosTripulacion::query()
            ->selectRaw('placa, SUM(COALESCE(alertas_velocidad_curvas, 0)) as total')
            ->whereMonth('fecha', $mesActual)
            ->whereYear('fecha', $anioActual)
            ->whereNotNull('placa')
            ->where('placa', '!=', '')
            ->groupBy('placa')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['placa' => $r->placa, 'total' => (int) $r->total]);

        // ── PolarArea por mes (desde eventos_tripulacion) ─────────────────────
        $MESES = [
            1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',
            7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic',
        ];

        $datosMes = EventosTripulacion::query()
            ->selectRaw('MONTH(fecha) as mes, SUM(COALESCE(alertas_velocidad_curvas, 0)) as total')
            ->whereYear('fecha', $anioActual)
            ->when($hayFiltroPlaca, fn ($q) => $q->whereIn('placa', $placasSeleccionadas))
            ->groupByRaw('MONTH(fecha)')
            ->get()
            ->keyBy('mes');

        // Placas con eventos por mes (para el tooltip del PolarArea)
        $placasPorMesRaw = EventosTripulacion::query()
            ->selectRaw('MONTH(fecha) as mes, placa')
            ->whereYear('fecha', $anioActual)
            ->whereNotNull('placa')
            ->where('placa', '!=', '')
            ->where('alertas_velocidad_curvas', '>', 0)
            ->when($hayFiltroPlaca, fn ($q) => $q->whereIn('placa', $placasSeleccionadas))
            ->distinct()
            ->get()
            ->groupBy('mes')
            ->map(fn ($rows) => $rows->pluck('placa')->sort()->values()->toArray());

        $porMes = collect($MESES)->map(fn ($label, $num) => [
            'mes'    => $label,
            'total'  => (int) ($datosMes[$num]->total ?? 0),
            'placas' => $placasPorMesRaw[$num] ?? [],
        ])->values();

        // ── Lista de placas disponibles para el multiselect manual ────────────
        $placas = EventosTripulacion::whereNotNull('placa')
            ->where('placa', '!=', '')
            ->distinct()
            ->orderBy('placa')
            ->pluck('placa');

        // ── Lista de colaboradores que tienen eventos registrados ─────────────
        $cedulasConEventos = EventosTripulacion::whereNotNull('documento')
            ->distinct()
            ->pluck('documento');

        $colaboradores = Colaborador::whereIn('cedula', $cedulasConEventos)
            ->orderBy('nombres')
            ->get(['cedula', 'nombres', 'apellidos'])
            ->map(fn ($c) => [
                'cedula' => $c->cedula,
                'nombre' => trim($c->nombres . ' ' . $c->apellidos),
            ]);

        // ── Centro del mapa ───────────────────────────────────────────────────
        $centro = $puntos->isNotEmpty()
            ? ['lat' => round($puntos->avg('lat'), 4), 'lon' => round($puntos->avg('lon'), 4)]
            : ['lat' => 4.7110, 'lon' => -74.0721];

        return Inertia::render('reparto/indicadores/index', [
            'puntos'                     => $puntos,
            'porFecha'                   => $porFecha,
            'porPlaca'                   => $porPlaca,
            'porMes'                     => $porMes,
            'centro'                     => $centro,
            'placas'                     => $placas,
            'placasSeleccionadas'        => $hayFiltroColaborador ? [] : $placasSeleccionadas,
            'colaboradores'              => $colaboradores,
            'colaboradoresSeleccionados' => $colaboradoresSeleccionados,
            'placasDelColaborador'       => $placasPorColaborador,
            'filters'                    => [
                'fecha_desde' => $fechaDesdeFiltro,
                'fecha_hasta' => $fechaHastaFiltro,
                'placas'      => $hayFiltroColaborador ? [] : $placasSeleccionadas,
                'cedulas'     => $cedulas,
            ],
            'totales' => [
                'puntos'  => $puntos->count(),
                'eventos' => $puntos->sum('eventos'),
            ],
            'mesLabel'   => now()->locale('es')->isoFormat('MMMM YYYY'),
        ]);
    }
}
