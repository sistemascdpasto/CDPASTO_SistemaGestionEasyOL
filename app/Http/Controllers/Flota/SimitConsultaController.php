<?php

namespace App\Http\Controllers\Flota;

use App\Http\Controllers\Controller;
use App\Models\SimitConsulta;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SimitConsultaController extends Controller
{
    /**
     * Solo lectura: estos datos los genera la automatización SIMIT que
     * corre en una PC local (ver POST /api/simit/consultas), no se
     * crean/editan desde la web.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->trim()->toString();
        $fechaDesde = $request->string('fecha_desde')->trim()->toString();
        $fechaHasta = $request->string('fecha_hasta')->trim()->toString();

        $consultas = SimitConsulta::query()
            ->when($search !== '', fn ($query) => $query->where('placa', 'like', "%{$search}%"))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($fechaDesde !== '', fn ($query) => $query->whereDate('fecha_hora', '>=', $fechaDesde))
            ->when($fechaHasta !== '', fn ($query) => $query->whereDate('fecha_hora', '<=', $fechaHasta))
            ->orderByDesc('fecha_hora')
            ->paginate(20)
            ->withQueryString();

        $actuales = $this->estadoActual();

        return Inertia::render('flota/simit-consultas/index', [
            'consultas' => $consultas,
            'actuales' => $actuales,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'indicadores' => [
                'resumen' => [
                    'total_placas' => $actuales->count(),
                    'con_comparendos' => $actuales->where('status', 'ok')->count(),
                    'sin_comparendos' => $actuales->where('status', 'sin_comparendos')->count(),
                    'requieren_atencion' => $actuales->whereIn('status', ['captcha', 'error'])->count(),
                ],
                'tendencia_diaria' => $this->tendenciaDiaria(),
                'top_placas_comparendos' => $this->topPlacasComparendos(),
            ],
        ]);
    }

    /**
     * Una fila por placa: su consulta más reciente. Equivalente a lo que
     * el script local guarda en data/{placa}_ultimo.json, separado del
     * histórico completo (data/historico.csv).
     */
    private function estadoActual(): Collection
    {
        // Se ordena por fecha_hora (no por id) para que "más reciente"
        // sea siempre la consulta cronológicamente más nueva, incluso si
        // llegara alguna vez desordenada (reintentos, backfill).
        return SimitConsulta::query()
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id')
            ->get()
            ->unique('placa')
            ->sortBy('placa')
            ->values();
    }

    /**
     * Consultas por día en los últimos 30 días, desglosadas por estado
     * (para el stacked bar de tendencia). Se agrupa en PHP en vez de con
     * funciones de fecha de SQL para que el resultado sea igual en MySQL
     * (producción) y sqlite (tests), y se rellenan los días sin datos.
     */
    private function tendenciaDiaria(): array
    {
        $desde = CarbonImmutable::now()->subDays(29)->startOfDay();

        $porDia = SimitConsulta::query()
            ->where('fecha_hora', '>=', $desde)
            ->get(['fecha_hora', 'status'])
            ->groupBy(fn (SimitConsulta $consulta) => $consulta->fecha_hora->format('Y-m-d'));

        $dias = [];
        for ($fecha = $desde; $fecha <= CarbonImmutable::now(); $fecha = $fecha->addDay()) {
            $clave = $fecha->format('Y-m-d');
            $delDia = $porDia->get($clave, collect());

            $dias[] = [
                'fecha' => $clave,
                'sin_comparendos' => $delDia->where('status', 'sin_comparendos')->count(),
                'ok' => $delDia->where('status', 'ok')->count(),
                'captcha' => $delDia->where('status', 'captcha')->count(),
                'error' => $delDia->where('status', 'error')->count(),
            ];
        }

        return $dias;
    }

    /**
     * Placas que más veces aparecieron con comparendos pendientes en todo
     * el histórico (no solo el estado actual): ayuda a detectar
     * reincidentes, no solo el corte de hoy.
     */
    private function topPlacasComparendos(): array
    {
        return SimitConsulta::query()
            ->where('status', 'ok')
            ->select('placa', DB::raw('count(*) as total'))
            ->groupBy('placa')
            ->orderByDesc('total')
            ->limit(10)
            ->get()
            ->map(fn ($fila) => ['placa' => $fila->placa, 'total' => (int) $fila->total])
            ->all();
    }

    /**
     * Sirve el pantallazo (guardado como BLOB, no como archivo en disco)
     * para mostrarlo/descargarlo desde la vista.
     */
    public function screenshot(SimitConsulta $consulta): HttpResponse
    {
        abort_if(! $consulta->screenshot, 404);

        return response($consulta->screenshot, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="'.($consulta->screenshot_nombre ?: 'pantallazo.png').'"',
        ]);
    }
}
