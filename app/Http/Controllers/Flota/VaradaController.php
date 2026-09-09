<?php

namespace App\Http\Controllers\Flota;

use App\Http\Controllers\Controller;
use App\Models\Flota\Varada;
use App\Models\Flota\Vehiculo;
use App\Services\Flota\VaradaImportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Inertia\Inertia;
use Inertia\Response;

class VaradaController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = [
            'anio' => $request->integer('anio') ?: null,
            'mes' => $request->integer('mes') ?: null,
            'placa' => $request->string('placa')->trim()->toString(),
            'sistema' => $request->string('sistema')->trim()->toString(),
            'fecha_desde' => $request->string('fecha_desde')->trim()->toString(),
            'fecha_hasta' => $request->string('fecha_hasta')->trim()->toString(),
        ];

        $data = $this->filteredQuery($filtros)
            ->orderByDesc('fecha_reportada')
            ->paginate(20)
            ->withQueryString();

        $registros = $this->filteredQuery($filtros)->get();

        return Inertia::render('flota/varadas/index', [
            'registros' => $data,
            'filters' => $filtros,
            'catalogos' => [
                'placas' => Varada::query()->distinct()->whereNotNull('placa')->orderBy('placa')->pluck('placa'),
                'sistemas' => Varada::query()->distinct()->whereNotNull('sistema')->orderBy('sistema')->pluck('sistema'),
                'anios' => Varada::query()->pluck('fecha_reportada')->map(fn ($fecha) => $fecha->year)->unique()->sort()->values(),
                // Placas de la flota (Documentación de Flota) para el formulario de
                // registro manual: se elige el vehículo, no se digita la placa.
                'placas_flota' => Vehiculo::query()->where('is_active', true)->orderBy('placa')->pluck('placa'),
            ],
            'indicadores' => $this->indicadores($registros),
            'mapa_puntos' => $this->mapaPuntos($registros),
        ]);
    }

    private function filteredQuery(array $filtros): Builder
    {
        return Varada::query()
            ->when($filtros['anio'], fn ($q, $anio) => $q->whereYear('fecha_reportada', $anio))
            ->when($filtros['mes'], fn ($q, $mes) => $q->whereMonth('fecha_reportada', $mes))
            ->when($filtros['placa'] !== '', fn ($q) => $q->where('placa', $filtros['placa']))
            ->when($filtros['sistema'] !== '', fn ($q) => $q->where('sistema', $filtros['sistema']))
            ->when($filtros['fecha_desde'] !== '', fn ($q) => $q->whereDate('fecha_reportada', '>=', $filtros['fecha_desde']))
            ->when($filtros['fecha_hasta'] !== '', fn ($q) => $q->whereDate('fecha_reportada', '<=', $filtros['fecha_hasta']));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validarVarada($request);
        $validated['origen'] = 'manual';
        $validated['created_by'] = $request->user()->id;

        Varada::create($validated);

        return redirect()->route('flota.varadas.index')
            ->with('status', ['message' => 'Varada registrada correctamente.', 'type' => 'success']);
    }

    public function update(Request $request, Varada $varada): RedirectResponse
    {
        $varada->update($this->validarVarada($request));

        return redirect()->route('flota.varadas.index')
            ->with('status', ['message' => 'Varada actualizada correctamente.', 'type' => 'success']);
    }

    public function destroy(Varada $varada): RedirectResponse
    {
        $varada->delete();

        return redirect()->route('flota.varadas.index')
            ->with('status', ['message' => 'Varada eliminada.', 'type' => 'success']);
    }

    private function validarVarada(Request $request): array
    {
        return $request->validate([
            'placa' => 'required|string|max:20',
            'fecha_reportada' => 'required|date',
            'fecha_asistencia' => 'nullable|date',
            'fecha_solucion' => 'nullable|date|after_or_equal:fecha_reportada',
            'sistema' => 'nullable|string|max:100',
            'tipo_falla' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'causa_probable' => 'nullable|string',
            'repetitiva' => 'boolean',
            'ruta' => 'nullable|string|max:255',
            'lugar' => 'nullable|string|max:255',
            'proveedor' => 'nullable|string|max:255',
            'tipo_solucion' => 'nullable|string|max:255',
            'impacto' => 'nullable|string|max:255',
            'gravedad' => 'nullable|integer|min:1|max:5',
            'observaciones' => 'nullable|string',
            'latitud' => 'nullable|numeric|between:-90,90',
            'longitud' => 'nullable|numeric|between:-180,180',
        ]);
    }

    public function importar(Request $request, VaradaImportService $service): RedirectResponse
    {
        $request->validate([
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|file|mimes:xlsx,xls,xlsm,csv|max:20480',
        ]);

        $rutas = collect($request->file('archivos'))
            ->mapWithKeys(fn ($archivo) => [$archivo->getRealPath() => $archivo->getClientOriginalName()])
            ->all();

        $resultado = $service->importar($rutas, $request->user()->id);

        if ($resultado['varadas_creadas'] + $resultado['varadas_actualizadas'] > 0) {
            $mensaje = "Excel procesado: {$resultado['varadas_creadas']} varadas nuevas, "
                ." {$resultado['varadas_actualizadas']} actualizadas y {$resultado['ubicaciones_cargadas']} ubicaciones cargadas.";
            $tipo = 'success';
        } else {
            $mensaje = 'No se pudieron procesar los datos del archivo. Verifique que tenga la hoja de varadas y la de coordenadas con sus encabezados originales.';
            $tipo = 'error';
        }

        return redirect()->route('flota.varadas.index')
            ->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }

    /**
     * @param  Collection<int, Varada>  $registros
     */
    private function indicadores(Collection $registros): array
    {
        $resueltos = $registros->filter(fn (Varada $v) => ! $v->esta_abierta);
        $totalRepetitivas = $registros->where('repetitiva', true)->count();

        return [
            'resumen' => [
                'total' => $registros->count(),
                'abiertas' => $registros->where('esta_abierta', true)->count(),
                'pct_repetitivas' => $registros->count() > 0 ? round($totalRepetitivas * 100 / $registros->count(), 1) : 0,
                'promedio_tfs_horas' => $resueltos->isNotEmpty() ? round($resueltos->avg('tfs_horas'), 2) : null,
                'promedio_gt_horas' => $resueltos->isNotEmpty() ? round((float) $resueltos->pluck('gt_horas')->filter(fn ($v) => $v !== null)->avg(), 2) : null,
                'promedio_gravedad' => round((float) $registros->whereNotNull('gravedad')->avg('gravedad'), 1),
                'sin_coordenadas' => $registros->filter(fn (Varada $v) => $v->latitud === null || $v->longitud === null)->count(),
            ],
            'por_mes' => $this->agruparPor($registros, fn (Varada $v) => $v->fecha_reportada->format('Y-m'), 'total')
                ->sortKeys()
                ->map(fn ($fila, $clave) => ['mes' => $clave, 'total' => $fila['total']])
                ->values()
                ->all(),
            'dias_fs_por_placa' => $this->sumarPor($resueltos, 'placa', 'dias_fs', 'placa'),
            'dias_fs_por_sistema' => $this->sumarPor($resueltos, 'sistema', 'dias_fs', 'sistema'),
            'por_ruta' => $this->agruparPor($registros, fn (Varada $v) => $v->ruta ?: 'Sin ruta', 'total')
                ->map(fn ($fila, $clave) => ['ruta' => $clave, 'total' => $fila['total']])
                ->sortByDesc('total')
                ->values()
                ->all(),
            'top_causas' => $registros->whereNotNull('causa_probable')
                ->groupBy('causa_probable')
                ->map(fn ($grupo, $causa) => ['causa' => $causa, 'total' => $grupo->count()])
                ->sortByDesc('total')
                ->take(10)
                ->values()
                ->all(),
            'top_proveedores' => $registros->whereNotNull('proveedor')
                ->groupBy('proveedor')
                ->map(fn ($grupo, $proveedor) => ['proveedor' => $proveedor, 'total' => $grupo->count()])
                ->sortByDesc('total')
                ->take(10)
                ->values()
                ->all(),
            'distribucion_gravedad' => $registros->whereNotNull('gravedad')
                ->groupBy('gravedad')
                ->map(fn ($grupo, $gravedad) => ['gravedad' => (int) $gravedad, 'total' => $grupo->count()])
                ->sortBy('gravedad')
                ->values()
                ->all(),
            'horas_promedio_por_sistema' => $resueltos->whereNotNull('sistema')
                ->groupBy('sistema')
                ->map(fn ($grupo, $sistema) => ['sistema' => $sistema, 'horas' => round($grupo->avg('tfs_horas'), 1)])
                ->sortByDesc('horas')
                ->values()
                ->all(),
        ];
    }

    private function agruparPor(Collection $registros, callable $clave, string $campoTotal): SupportCollection
    {
        return $registros->groupBy($clave)->map(fn ($grupo) => [$campoTotal => $grupo->count()]);
    }

    private function sumarPor(Collection $registros, string $campoAgrupar, string $atributoSumar, string $etiqueta): array
    {
        return $registros->whereNotNull($campoAgrupar)
            ->groupBy($campoAgrupar)
            ->map(fn ($grupo, $valor) => [$etiqueta => $valor, 'total' => round($grupo->sum($atributoSumar), 2)])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Varada>  $registros
     */
    private function mapaPuntos(Collection $registros): array
    {
        return $registros
            ->filter(fn (Varada $v) => $v->latitud !== null && $v->longitud !== null)
            ->groupBy(fn (Varada $v) => round((float) $v->latitud, 4).','.round((float) $v->longitud, 4))
            ->map(function (Collection $grupo) {
                $primero = $grupo->first();

                return [
                    'lat' => (float) $primero->latitud,
                    'lng' => (float) $primero->longitud,
                    'peso' => $grupo->count(),
                    'lugar' => $primero->ruta ?: $primero->lugar,
                    'dias_fs_total' => round((float) $grupo->pluck('dias_fs')->filter(fn ($v) => $v !== null)->sum(), 2),
                    'abiertas' => $grupo->where('esta_abierta', true)->count(),
                ];
            })
            ->values()
            ->all();
    }
}
