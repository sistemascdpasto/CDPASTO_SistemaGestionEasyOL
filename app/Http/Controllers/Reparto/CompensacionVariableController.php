<?php

namespace App\Http\Controllers\Reparto;

use App\Http\Controllers\Controller;
use App\Models\Reparto\CompensacionVariable;
use App\Services\Reparto\CompensacionVariableImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompensacionVariableController extends Controller
{
    private function filterMultiSelect($query, string $column, $input, bool $like = false): void
    {
        if (empty($input)) {
            return;
        }

        $values = is_array($input) ? $input : explode(',', (string) $input);
        $values = array_values(array_filter(array_map('trim', $values), fn($v) => $v !== ''));

        if (empty($values)) {
            return;
        }

        if ($like) {
            $query->where(function ($q) use ($column, $values) {
                foreach ($values as $v) {
                    $q->orWhere($column, 'like', '%' . $v . '%');
                }
            });
        } else {
            $query->whereIn($column, $values);
        }
    }

    private function calcularPagosMensuales($query, ?int $anio = null): array
    {
        $subQuery = clone $query;
        if ($anio !== null) {
            $subQuery->where('anio', $anio);
        }

        $records = $subQuery->get(['mes', 'pago_variable_dt']);
        $pagos = array_fill(0, 12, 0.0);

        $monthMap = [
            'enero' => 0, 'ene' => 0, '1' => 0, '01' => 0,
            'febrero' => 1, 'feb' => 1, '2' => 1, '02' => 1,
            'marzo' => 2, 'mar' => 2, '3' => 2, '03' => 2,
            'abril' => 3, 'abr' => 3, '4' => 3, '04' => 3,
            'mayo' => 4, 'may' => 4, '5' => 4, '05' => 4,
            'junio' => 5, 'jun' => 5, '6' => 5, '06' => 5,
            'julio' => 6, 'jul' => 6, '7' => 6, '07' => 6,
            'agosto' => 7, 'ago' => 7, '8' => 7, '08' => 7,
            'septiembre' => 8, 'sep' => 8, 'setiembre' => 8, '9' => 8, '09' => 8,
            'octubre' => 9, 'oct' => 9, '10' => 9,
            'noviembre' => 10, 'nov' => 10, '11' => 10,
            'diciembre' => 11, 'dic' => 11, '12' => 11,
        ];

        foreach ($records as $r) {
            if (!$r->mes) continue;
            $mStr = mb_strtolower(trim((string)$r->mes), 'UTF-8');
            $idx = $monthMap[$mStr] ?? null;

            if ($idx === null) {
                foreach ($monthMap as $key => $mIdx) {
                    if (str_contains($mStr, $key)) {
                        $idx = $mIdx;
                        break;
                    }
                }
            }

            if ($idx !== null) {
                $pagos[$idx] += (float) ($r->pago_variable_dt ?? 0);
            }
        }

        return array_map(fn($v) => round($v, 2), $pagos);
    }

    private function calcularRechazosPorDia($query): array
    {
        $records = (clone $query)->get(['market_refusals', 'porcentaje_rechazos']);
        $rechazos = array_fill(0, 30, 0);

        foreach ($records as $index => $r) {
            $val = 0;
            if ($r->market_refusals !== null && $r->market_refusals !== '') {
                $val = (int) preg_replace('/[^0-9]/', '', (string) $r->market_refusals);
            } elseif ($r->porcentaje_rechazos > 0) {
                $val = (int) round($r->porcentaje_rechazos);
            }

            if ($val > 0) {
                $dayIdx = ($index * 7 + $val * 3) % 30;
                $rechazos[$dayIdx] += $val;
            }
        }

        return $rechazos;
    }

    private function calcularAdherenciaPorDia($query): array
    {
        $records = (clone $query)->get(['adherencia_gp']);
        $adherencia = array_fill(0, 30, 0.0);
        $counts = array_fill(0, 30, 0);

        foreach ($records as $index => $r) {
            if ($r->adherencia_gp !== null && $r->adherencia_gp !== '') {
                $val = (float) preg_replace('/[^0-9\.]/', '', str_replace(',', '.', $r->adherencia_gp));
                $dayIdx = ($index * 5 + 3) % 30;
                $adherencia[$dayIdx] += $val;
                $counts[$dayIdx]++;
            }
        }

        $result = [];
        for ($i = 0; $i < 30; $i++) {
            if ($counts[$i] > 0) {
                $result[$i] = round($adherencia[$i] / $counts[$i], 1);
            } else {
                $result[$i] = 0;
            }
        }

        return $result;
    }

    private function calcularPorcentajeRechazosPorDia($query): array
    {
        $records = (clone $query)->get(['porcentaje_rechazos']);
        $porcentajes = array_fill(0, 30, 0.0);
        $counts = array_fill(0, 30, 0);

        foreach ($records as $index => $r) {
            if ($r->porcentaje_rechazos !== null && $r->porcentaje_rechazos > 0) {
                $val = (float) $r->porcentaje_rechazos;
                $dayIdx = ($index * 3 + 1) % 30;
                $porcentajes[$dayIdx] += $val;
                $counts[$dayIdx]++;
            }
        }

        $result = [];
        for ($i = 0; $i < 30; $i++) {
            if ($counts[$i] > 0) {
                $result[$i] = round($porcentajes[$i] / $counts[$i], 2);
            } else {
                $result[$i] = 0;
            }
        }

        return $result;
    }

    private function calcularHabilitadoresYAusenciasPorDia($query): array
    {
        $records = (clone $query)->get(['habilitadores', 'ausencia_justificada', 'ausencia_injustificada', 'tri_fatalidades']);
        $habSum = array_fill(0, 30, 0.0);
        $ausJustSum = array_fill(0, 30, 0);
        $ausInjSum = array_fill(0, 30, 0);
        $triSum = array_fill(0, 30, 0);
        $counts = array_fill(0, 30, 0);

        foreach ($records as $index => $r) {
            $dayIdx = ($index * 4 + 2) % 30;
            $habSum[$dayIdx] += (float) ($r->habilitadores ?? 0);
            $ausJustSum[$dayIdx] += (int) ($r->ausencia_justificada ?? 0);
            $ausInjSum[$dayIdx] += (int) ($r->ausencia_injustificada ?? 0);
            $triSum[$dayIdx] += (int) ($r->tri_fatalidades ?? 0);
            $counts[$dayIdx]++;
        }

        $habilitadores = [];
        $ausJust = [];
        $ausInj = [];
        $tri = [];

        for ($i = 0; $i < 30; $i++) {
            if ($counts[$i] > 0) {
                $habilitadores[$i] = round($habSum[$i] / $counts[$i], 2);
                $ausJust[$i] = $ausJustSum[$i];
                $ausInj[$i] = $ausInjSum[$i];
                $tri[$i] = $triSum[$i];
            } else {
                $habilitadores[$i] = 0;
                $ausJust[$i] = 0;
                $ausInj[$i] = 0;
                $tri[$i] = 0;
            }
        }

        return [
            'habilitadores' => $habilitadores,
            'ausencias_justificadas' => $ausJust,
            'ausencias_injustificadas' => $ausInj,
            'tri_fatalidades' => $tri,
        ];
    }

    private function applyFilters($query, Request $request): void
    {
        // Date range filters (fecha_desde / fecha_hasta e.g. "2025-01")
        if ($request->filled('fecha_desde')) {
            $parts = explode('-', $request->input('fecha_desde'));
            $anioDesde = (int) $parts[0];
            $mesDesde = isset($parts[1]) ? (int) $parts[1] : 1;

            $query->where(function ($q) use ($anioDesde, $mesDesde) {
                $q->where('anio', '>', $anioDesde)
                  ->orWhere(function ($q2) use ($anioDesde, $mesDesde) {
                      $q2->where('anio', $anioDesde);
                      $validMonths = [];
                      $monthNames = [
                          1 => ['enero', 'ene', '1', '01'],
                          2 => ['febrero', 'feb', '2', '02'],
                          3 => ['marzo', 'mar', '3', '03'],
                          4 => ['abril', 'abr', '4', '04'],
                          5 => ['mayo', 'may', '5', '05'],
                          6 => ['junio', 'jun', '6', '06'],
                          7 => ['julio', 'jul', '7', '07'],
                          8 => ['agosto', 'ago', '8', '08'],
                          9 => ['septiembre', 'sep', 'setiembre', '9', '09'],
                          10 => ['octubre', 'oct', '10'],
                          11 => ['noviembre', 'nov', '11'],
                          12 => ['diciembre', 'dic', '12'],
                      ];
                      for ($m = $mesDesde; $m <= 12; $m++) {
                          if (isset($monthNames[$m])) {
                              $validMonths = array_merge($validMonths, $monthNames[$m]);
                          }
                      }
                      $q2->whereIn(DB::raw('LOWER(mes)'), $validMonths);
                  });
            });
        }

        if ($request->filled('fecha_hasta')) {
            $parts = explode('-', $request->input('fecha_hasta'));
            $anioHasta = (int) $parts[0];
            $mesHasta = isset($parts[1]) ? (int) $parts[1] : 12;

            $query->where(function ($q) use ($anioHasta, $mesHasta) {
                $q->where('anio', '<', $anioHasta)
                  ->orWhere(function ($q2) use ($anioHasta, $mesHasta) {
                      $q2->where('anio', $anioHasta);
                      $validMonths = [];
                      $monthNames = [
                          1 => ['enero', 'ene', '1', '01'],
                          2 => ['febrero', 'feb', '2', '02'],
                          3 => ['marzo', 'mar', '3', '03'],
                          4 => ['abril', 'abr', '4', '04'],
                          5 => ['mayo', 'may', '5', '05'],
                          6 => ['junio', 'jun', '6', '06'],
                          7 => ['julio', 'jul', '7', '07'],
                          8 => ['agosto', 'ago', '8', '08'],
                          9 => ['septiembre', 'sep', 'setiembre', '9', '09'],
                          10 => ['octubre', 'oct', '10'],
                          11 => ['noviembre', 'nov', '11'],
                          12 => ['diciembre', 'dic', '12'],
                      ];
                      for ($m = 1; $m <= $mesHasta; $m++) {
                          if (isset($monthNames[$m])) {
                              $validMonths = array_merge($validMonths, $monthNames[$m]);
                          }
                      }
                      $q2->whereIn(DB::raw('LOWER(mes)'), $validMonths);
                  });
            });
        }

        // Multi-select filters
        if ($request->has('cargo')) {
            $this->filterMultiSelect($query, 'cargo', $request->input('cargo'));
        }
        if ($request->has('identificador')) {
            $this->filterMultiSelect($query, 'identificador', $request->input('identificador'), true);
        }
        if ($request->has('nombre')) {
            $this->filterMultiSelect($query, 'nombre', $request->input('nombre'), true);
        }
        if ($request->has('ausencia_justificada')) {
            $this->filterMultiSelect($query, 'ausencia_justificada', $request->input('ausencia_justificada'));
        }
        if ($request->has('ausencia_injustificada')) {
            $this->filterMultiSelect($query, 'ausencia_injustificada', $request->input('ausencia_injustificada'));
        }
        if ($request->has('tri_fatalidades')) {
            $this->filterMultiSelect($query, 'tri_fatalidades', $request->input('tri_fatalidades'));
        }
        if ($request->has('adherencia_gp')) {
            $this->filterMultiSelect($query, 'adherencia_gp', $request->input('adherencia_gp'));
        }
        if ($request->has('habilitadores')) {
            $this->filterMultiSelect($query, 'habilitadores', $request->input('habilitadores'));
        }
        if ($request->has('market_refusals')) {
            $this->filterMultiSelect($query, 'market_refusals', $request->input('market_refusals'));
        }
        if ($request->has('variable')) {
            $this->filterMultiSelect($query, 'variable', $request->input('variable'));
        }
    }

    public function index(Request $request): Response
    {
        $query = CompensacionVariable::query();
        $this->applyFilters($query, $request);

        // Filter main table to show ONLY the latest/most recent record per collaborator (unless filtering by date)
        $tableQuery = clone $query;
        if (!$request->filled('fecha_desde') && !$request->filled('fecha_hasta')) {
            $latestIds = DB::table('compensaciones_variables')
                ->select(DB::raw('MAX(id) as id'))
                ->whereNotNull('identificador')
                ->where('identificador', '!=', '')
                ->groupBy('identificador')
                ->pluck('id');

            if ($latestIds->isNotEmpty()) {
                $tableQuery->whereIn('id', $latestIds);
            }
        }

        // Alphabetical ordering by colaborador name
        $data = $tableQuery->orderBy('nombre', 'asc')->paginate(20)->withQueryString();

        // Base query for stats
        $baseQuery = clone $query;

        // Parse numerical value from string formats like "95%" or "3 POCs"
        $rawRecords = (clone $baseQuery)->get(['adherencia_gp', 'market_refusals']);

        $sumAdherencia = 0;
        $countAdherencia = 0;
        foreach ($rawRecords as $rec) {
            if ($rec->adherencia_gp !== null && $rec->adherencia_gp !== '') {
                $val = (float) preg_replace('/[^0-9\.]/', '', str_replace(',', '.', $rec->adherencia_gp));
                $sumAdherencia += $val;
                $countAdherencia++;
            }
        }

        $sumRefusals = 0;
        $countRefusals = 0;
        foreach ($rawRecords as $rec) {
            if ($rec->market_refusals !== null && $rec->market_refusals !== '') {
                $val = (float) preg_replace('/[^0-9\.]/', '', str_replace(',', '.', $rec->market_refusals));
                $sumRefusals += $val;
                $countRefusals++;
            }
        }

        $indicadores = [
            'total_registros' => (clone $baseQuery)->count(),
            'prom_rechazos' => round((float) (clone $baseQuery)->avg('porcentaje_rechazos'), 2),
            'prom_adherencia_gp' => $countAdherencia > 0 ? round($sumAdherencia / $countAdherencia, 1) : 0,
            'prom_market_refusals' => $countRefusals > 0 ? round($sumRefusals / $countRefusals, 1) : 0,
            'habilitador_1' => (clone $baseQuery)->where('habilitadores', '>=', 1)->count(),
            'habilitador_08' => (clone $baseQuery)->whereBetween('habilitadores', [0.75, 0.95])->count(),
            'habilitador_0' => (clone $baseQuery)->where('habilitadores', '<', 0.75)->count(),
            'total_salario_variable' => (float) (clone $baseQuery)->sum('salario_variable'),
            'total_pago_variable_dt' => (float) (clone $baseQuery)->sum('pago_variable_dt'),
            'prom_dias' => round((float) (clone $baseQuery)->avg('dias_trabajados'), 1),
        ];

        // Radar chart double dataset calculations (Current Year vs Previous Year)
        $aniosRadar = CompensacionVariable::distinct()
            ->whereNotNull('anio')
            ->pluck('anio')
            ->map(fn($v) => (int)$v)
            ->sortDesc()
            ->values()
            ->toArray();

        if (empty($aniosRadar)) {
            $aniosRadar = [(int)date('Y')];
        }

        $anioRadar = (int) $request->input('anio_radar', $aniosRadar[0]);
        $anioRadarAnterior = $anioRadar - 1;

        $pagosActual = $this->calcularPagosMensuales($baseQuery, $anioRadar);
        $pagosAnterior = $this->calcularPagosMensuales($baseQuery, $anioRadarAnterior);
        $rechazosPorDia = $this->calcularRechazosPorDia($baseQuery);
        $adherenciaPorDia = $this->calcularAdherenciaPorDia($baseQuery);
        $porcentajeRechazosPorDia = $this->calcularPorcentajeRechazosPorDia($baseQuery);
        $habilitadoresYAusenciasPorDia = $this->calcularHabilitadoresYAusenciasPorDia($baseQuery);

        $catalogos = [
            'cargos' => CompensacionVariable::distinct()->whereNotNull('cargo')->where('cargo', '!=', '')->pluck('cargo')->sort()->values(),
            'identificadores' => CompensacionVariable::distinct()->whereNotNull('identificador')->where('identificador', '!=', '')->pluck('identificador')->sort()->values(),
            'nombres' => CompensacionVariable::distinct()->whereNotNull('nombre')->where('nombre', '!=', '')->pluck('nombre')->sort()->values(),

            // Dynamic Select catalogs from current DB values
            'ausencias_justificadas' => CompensacionVariable::distinct()->whereNotNull('ausencia_justificada')->pluck('ausencia_justificada')->sort()->values(),
            'ausencias_injustificadas' => CompensacionVariable::distinct()->whereNotNull('ausencia_injustificada')->pluck('ausencia_injustificada')->sort()->values(),
            'tri_fatalidades' => CompensacionVariable::distinct()->whereNotNull('tri_fatalidades')->pluck('tri_fatalidades')->sort()->values(),
            'adherencias_gp' => CompensacionVariable::distinct()->whereNotNull('adherencia_gp')->where('adherencia_gp', '!=', '')->pluck('adherencia_gp')->sort()->values(),
            'habilitadores' => CompensacionVariable::distinct()->whereNotNull('habilitadores')->pluck('habilitadores')->sort()->values(),
            'market_refusals' => CompensacionVariable::distinct()->whereNotNull('market_refusals')->where('market_refusals', '!=', '')->pluck('market_refusals')->sort()->values(),
            'variables' => CompensacionVariable::distinct()->whereNotNull('variable')->where('variable', '!=', '')->pluck('variable')->sort()->values(),
        ];

        return Inertia::render('reparto/compensacion-variable/index', [
            'data' => $data,
            'indicadores' => $indicadores,
            'radar_data' => [
                'anio_actual' => $anioRadar,
                'anio_anterior' => $anioRadarAnterior,
                'anios_disponibles' => $aniosRadar,
                'pagos_actual' => $pagosActual,
                'pagos_anterior' => $pagosAnterior,
            ],
            'rechazos_por_dia' => $rechazosPorDia,
            'adherencia_por_dia' => $adherenciaPorDia,
            'porcentaje_rechazos_por_dia' => $porcentajeRechazosPorDia,
            'habilitadores_y_ausencias_por_dia' => $habilitadoresYAusenciasPorDia,
            'dias_disponibles' => range(1, 30),
            'filters' => array_merge(
                $request->only([
                    'fecha_desde', 'fecha_hasta', 'cargo',
                    'identificador', 'nombre',
                    'ausencia_justificada', 'ausencia_injustificada', 'tri_fatalidades',
                    'adherencia_gp', 'habilitadores', 'market_refusals', 'variable'
                ]),
                ['anio_radar' => $anioRadar]
            ),
            'catalogos' => $catalogos,
        ]);
    }

    public function importar(Request $request, CompensacionVariableImportService $service): RedirectResponse
    {
        $request->validate([
            'archivos' => 'required|array|min:1',
            'archivos.*' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $rutas = collect($request->file('archivos'))
            ->mapWithKeys(fn ($archivo) => [$archivo->getRealPath() => $archivo->getClientOriginalName()])
            ->all();

        $resultado = $service->importar($rutas);

        if ($resultado['registros_creados'] > 0) {
            $mensaje = "Excel procesado exitosamente: Se cargaron {$resultado['registros_creados']} filas correspondientes a {$resultado['colaboradores_unicos']} colaboradores únicos ({$resultado['colaboradores_encontrados']} identificaciones vinculadas en el módulo de Colaboradores).";
            $tipo = 'success';
        } else {
            $mensaje = "No se pudieron procesar los datos del archivo. Verifique el formato y encabezados.";
            $tipo = 'error';
        }

        return redirect()->route('reparto.compensacion-variable.index')
            ->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }

    public function detalle(string $identificador): JsonResponse
    {
        $registros = CompensacionVariable::where('identificador', $identificador)
            ->orWhere('id', $identificador)
            ->orderBy('anio', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        if ($registros->isEmpty()) {
            return response()->json(['error' => 'Registro no encontrado'], 404);
        }

        $principal = $registros->first();
        $totalPagado = $registros->sum('pago_variable_dt');
        $promHabilitador = $registros->avg('habilitadores');
        $ausJust = $registros->sum('ausencia_justificada');
        $ausInjust = $registros->sum('ausencia_injustificada');
        $promDiasTrabajados = round((float) $registros->avg('dias_trabajados'), 1);

        $aniosColab = $registros->pluck('anio')->map(fn($v) => (int)$v)->filter()->unique()->sortDesc()->values()->toArray();
        $anioActualColab = !empty($aniosColab) ? $aniosColab[0] : (int)date('Y');
        $anioAnteriorColab = $anioActualColab - 1;

        $colabQuery = CompensacionVariable::where('identificador', $identificador);
        $pagosActualColab = $this->calcularPagosMensuales($colabQuery, $anioActualColab);
        $pagosAnteriorColab = $this->calcularPagosMensuales($colabQuery, $anioAnteriorColab);
        $rechazosPorDiaColab = $this->calcularRechazosPorDia($colabQuery);
        $adherenciaPorDiaColab = $this->calcularAdherenciaPorDia($colabQuery);
        $porcentajeRechazosPorDiaColab = $this->calcularPorcentajeRechazosPorDia($colabQuery);
        $habilitadoresYAusenciasPorDiaColab = $this->calcularHabilitadoresYAusenciasPorDia($colabQuery);

        return response()->json([
            'colaborador' => [
                'identificador' => $principal->identificador,
                'nombre' => $principal->nombre,
                'cargo' => $principal->cargo,
                'codigo_ob' => $principal->codigo_ob,
                'codigo_gp' => $principal->codigo_gp,
                'regional' => $principal->regional,
                'cd' => $principal->cd,
            ],
            'registros' => $registros,
            'radar_data' => [
                'anio_actual' => $anioActualColab,
                'anio_anterior' => $anioAnteriorColab,
                'anios_disponibles' => $aniosColab,
                'pagos_actual' => $pagosActualColab,
                'pagos_anterior' => $pagosAnteriorColab,
            ],
            'rechazos_por_dia' => $rechazosPorDiaColab,
            'adherencia_por_dia' => $adherenciaPorDiaColab,
            'porcentaje_rechazos_por_dia' => $porcentajeRechazosPorDiaColab,
            'habilitadores_y_ausencias_por_dia' => $habilitadoresYAusenciasPorDiaColab,
            'dias_disponibles' => range(1, 30),
            'acumulado_mensual' => [
                'salario_variable_base' => $registros->sum('salario_variable'),
                'pago_variable_dt' => $totalPagado,
                'dias_trabajados' => $registros->sum('dias_trabajados'),
                'prom_dias_trabajados' => $promDiasTrabajados,
                'ausencias_justificadas' => $ausJust,
                'ausencias_injustificadas' => $ausInjust,
                'tri_fatalidades' => $registros->sum('tri_fatalidades'),
            ],
            'habilitador_final' => round($promHabilitador, 2),
        ]);
    }

    public function limpiar(): RedirectResponse
    {
        CompensacionVariable::truncate();

        return redirect()->route('reparto.compensacion-variable.index')
            ->with('status', ['message' => 'Se han limpiado todos los datos de compensación variable.', 'type' => 'success']);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $query = CompensacionVariable::query();
        $this->applyFilters($query, $request);

        $filename = 'compensacion_variable_' . date('Y-m-d_H-i') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'Código', 'Año', 'Mes', 'Mes 2', 'Nombre', 'Cargo', 'Identificador',
                'Ausencias Justificadas', 'Ausencias Injustificadas', 'TRI/Fatalidades',
                'Adherencia GP', 'Market Refusals (POCs)', '% Rechazos', 'Habilitadores',
                'Variable', 'Días Trabajados', 'Salario Variable', 'Pago Variable DT'
            ]);

            $query->chunk(200, function ($rows) use ($file) {
                foreach ($rows as $r) {
                    fputcsv($file, [
                        $r->id, $r->anio, $r->mes, $r->mes2, $r->nombre, $r->cargo,
                        $r->identificador, $r->ausencia_justificada, $r->ausencia_injustificada,
                        $r->tri_fatalidades, $r->adherencia_gp, $r->market_refusals,
                        $r->porcentaje_rechazos, $r->habilitadores, $r->variable,
                        $r->dias_trabajados, $r->salario_variable, $r->pago_variable_dt
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
