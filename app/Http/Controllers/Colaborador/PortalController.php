<?php

namespace App\Http\Controllers\Colaborador;

use App\Http\Controllers\Controller;
use App\Models\Gente\ChecklistPlanPremiacion;
use App\Models\Seguridad\Aci;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\Incentivo;
use App\Services\Colaborador\ColaboradorResumenService;
use App\Services\Seguridad\EvaluacionCalculator;
use App\Services\Seguridad\IndiceRiesgoCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    public function index(Request $request, EvaluacionCalculator $evaluacion, IndiceRiesgoCalculator $riesgo, ColaboradorResumenService $resumenService): Response
    {
        $colaborador = $this->colaboradorDe($request);

        if (! $colaborador) {
            return Inertia::render('colaborador/sin-vincular');
        }

        return Inertia::render('dashboard/colaborador', [
            'colaborador' => [
                'id' => $colaborador->id,
                'nombre_completo' => $colaborador->nombre_completo,
                'cargo' => $colaborador->cargo,
                'turno' => $colaborador->turno,
                'area' => $colaborador->area,
                'imagen' => $colaborador->imagen,
            ],
            'estadoHoy' => $evaluacion->paraColaboradorHoy($colaborador),
            'jornadaAbierta' => $evaluacion->faltaRegistrarSalida($colaborador),
            'indiceRiesgo' => $riesgo->calcular($colaborador),
            'ultimasPruebas' => $colaborador->pruebasAlcoholemia()
                ->with('alcoholimetro:id,codigo')
                ->latest('fecha_hora')
                ->limit(5)
                ->get(),
            'ultimasCondiciones' => $colaborador->condicionesSalud()
                ->latest('fecha_hora')
                ->limit(5)
                ->get(),
            'alertasPendientes' => $colaborador->alertas()->where('atendida', false)->count(),
            'resumen' => $resumenService->resumen($colaborador, $request->user()->id),
        ]);
    }

    public function perfil(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        return Inertia::render('colaborador/perfil', [
            'colaborador' => $colaborador,
        ]);
    }

    public function pruebas(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        return Inertia::render('colaborador/pruebas', [
            'pruebas' => $colaborador->pruebasAlcoholemia()
                ->with('alcoholimetro:id,codigo')
                ->latest('fecha_hora')
                ->paginate(15),
        ]);
    }

    public function alertas(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        return Inertia::render('colaborador/alertas', [
            'alertas' => $colaborador->alertas()->latest()->paginate(15),
        ]);
    }

    public function miPlanPremiacion(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $mes = $request->integer('mes') ?: (int) now()->month;
        $anio = $request->integer('anio') ?: (int) now()->year;

        $normStr = function ($txt): string {
            $str = mb_strtoupper(trim((string) $txt), 'UTF-8');
            $str = str_replace(['Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'Ñ'], ['A', 'E', 'I', 'O', 'U', 'U', 'N'], $str);

            return preg_replace('/[^A-Z0-9]/', '', $str) ?? $str;
        };

        // ── ACIs del mes ─────────────────────────────────────────────────────
        $metaBase = 4;
        $aciRealizadas = Aci::whereMonth('fecha_incidente', $mes)
            ->whereYear('fecha_incidente', $anio)
            ->where('colaborador_id', $colaborador->id)
            ->count();
        $porcentajeAci = round(($aciRealizadas / $metaBase) * 100, 1);

        // ── Historial ACI últimos 6 meses ─────────────────────────────────
        $historialAci = [];
        for ($i = 5; $i >= 0; $i--) {
            $f = Carbon::create($anio, $mes, 1)->subMonths($i);
            $count = Aci::whereMonth('fecha_incidente', $f->month)
                ->whereYear('fecha_incidente', $f->year)
                ->where('colaborador_id', $colaborador->id)
                ->count();
            $historialAci[] = [
                'mes' => $f->translatedFormat('M Y'),
                'total' => $count,
                'pct' => round(($count / $metaBase) * 100, 1),
                'cumple' => $count >= $metaBase,
            ];
        }

        // ── OWD Ruta ──────────────────────────────────────────────────────
        $preguntasRuta = DB::table('evaluacion_owd_preguntas')
            ->join('evaluaciones_owd', 'evaluacion_owd_preguntas.evaluacion_owd_id', '=', 'evaluaciones_owd.id')
            ->whereMonth('evaluaciones_owd.fecha_evaluacion', $mes)
            ->whereYear('evaluaciones_owd.fecha_evaluacion', $anio)
            ->where(function ($q) {
                $q->whereRaw("LOWER(TRIM(evaluacion_owd_preguntas.actividad)) = 'ruta'")
                    ->orWhereRaw("LOWER(TRIM(evaluacion_owd_preguntas.actividad)) = '\"ruta\"'")
                    ->orWhereRaw("LOWER(TRIM(evaluacion_owd_preguntas.actividad)) = '[\"ruta\"]'");
            })
            ->where(function ($q) use ($colaborador) {
                $q->where('evaluaciones_owd.colaborador_id', $colaborador->id)
                    ->orWhere('evaluaciones_owd.qr_safety', $colaborador->codigo_qr_skap);
            })
            ->get(['evaluacion_owd_preguntas.puntuacion']);

        $noOk = $preguntasRuta->filter(fn ($p) => str_contains(strtolower((string) $p->puntuacion), 'no ok'))->count();
        $ok = $preguntasRuta->filter(fn ($p) => str_contains(strtolower((string) $p->puntuacion), 'ok') && ! str_contains(strtolower((string) $p->puntuacion), 'no ok'))->count();
        $owdRuta = ($ok + $noOk) > 0 ? ($noOk > 0 ? 0.0 : 100.0) : null;

        // ── Calificaciones ────────────────────────────────────────────────
        $promedioCalif = DB::table('colaborador_calificaciones')
            ->where('identificacion', $colaborador->cedula)
            ->whereNotNull('nota_modulo')
            ->avg('nota_modulo');
        $promedioCalif = $promedioCalif !== null ? round((float) $promedioCalif, 1) : null;

        // ── DPO Academy ───────────────────────────────────────────────────
        $estaEnDpo = DB::table('dpo_academy')
            ->where(function ($q) use ($colaborador, $normStr) {
                $q->where('colaborador_id', $colaborador->id)
                    ->orWhereRaw('UPPER(REGEXP_REPLACE(qr_safety,"[^A-Z0-9]","")) = ?', [$normStr($colaborador->codigo_qr_skap ?? '')])
                    ->orWhereRaw('UPPER(REGEXP_REPLACE(nombre,"[^A-Z0-9]","")) = ?', [$normStr($colaborador->nombre_completo ?? '')]);
            })->exists();

        // ── Ausentismo ────────────────────────────────────────────────────
        $ausentismo = DB::table('ausentismos')
            ->whereMonth('fecha', $mes)->whereYear('fecha', $anio)
            ->where(function ($q) use ($colaborador) {
                $q->where('colaborador_id', $colaborador->id)
                    ->orWhere('identificador', $colaborador->cedula);
            })->get();
        $tieneIncapacidad = $ausentismo->contains(fn ($r) => in_array(trim((string) ($r->entro_1 ?? '')), ['', '00:00', '00:00:00', '0', '--:--'], true) &&
            in_array(trim((string) ($r->entro_2 ?? '')), ['', '00:00', '00:00:00', '0', '--:--'], true)
        );
        $porcentajeAusentismo = $ausentismo->isEmpty() ? null : ($tieneIncapacidad ? 0.0 : 100.0);

        // ── Malas Marcaciones ─────────────────────────────────────────────
        $tieneMalasMarcaciones = DB::table('correcciones_marcaciones')
            ->where(function ($q) use ($colaborador, $normStr) {
                $q->whereRaw('UPPER(REGEXP_REPLACE(identificacion,"[^A-Z0-9]","")) = ?', [$normStr($colaborador->cedula)])
                    ->orWhereRaw('UPPER(REGEXP_REPLACE(nombre_completo,"[^A-Z0-9]","")) = ?', [$normStr($colaborador->nombre_completo ?? '')]);
            })->exists();

        // ── Eventos Tripulación ───────────────────────────────────────────
        // Se traen TODOS los registros del mes para calcular el promedio correctamente
        $eventosColaborador = DB::table('eventos_tripulacion')
            ->whereMonth('fecha', $mes)->whereYear('fecha', $anio)
            ->where(function ($q) use ($colaborador, $normStr) {
                $q->whereRaw('UPPER(REGEXP_REPLACE(documento,"[^A-Z0-9]","")) = ?', [$normStr($colaborador->cedula)])
                    ->orWhereRaw('UPPER(REGEXP_REPLACE(nombre,"[^A-Z0-9]","")) = ?', [$normStr($colaborador->nombre_completo ?? '')]);
            })
            ->select(['rechazos', 'adherencia_tiempo', 'rmd', 'adherencia_checklist_pre', 'adherencia_checklist_post'])
            ->get();

        $evento = $eventosColaborador->first();

        // Solo aplica checklist para conductores (Default 100% / Aprobado)
        $esConductorPortal = str_contains(strtoupper((string)($colaborador->cargo ?? '')), 'CONDUCTOR');

        $manualCheck = ChecklistPlanPremiacion::where('colaborador_id', $colaborador->id)
            ->where('mes', $mes)
            ->where('anio', $anio)
            ->first();

        $clPreAprobado  = $manualCheck ? (bool)$manualCheck->cl_pre : true;
        $clPostAprobado = $manualCheck ? (bool)$manualCheck->cl_post : true;

        $umbralCl = \App\Http\Controllers\Gente\PlanPremiacionController::UMBRAL_CHECKLIST;

        // ── SAC ───────────────────────────────────────────────────────────
        $casosSac = DB::table('sac')
            ->whereMonth('fecha', $mes)->whereYear('fecha', $anio)
            ->where('colaborador_id', $colaborador->id)
            ->count();

        // ── Armar métricas ────────────────────────────────────────────────
        $metricas = [
            'aci' => ['valor' => $porcentajeAci,       'label' => "{$porcentajeAci}%",     'pilar' => 'Seguridad', 'peso' => 10,  'emoji' => '🛡️', 'titulo' => 'ACI',                'meta_desc' => '(Realizadas ÷ 4) × 100'],
            'owd' => ['valor' => $owdRuta,              'label' => $owdRuta !== null ? "{$owdRuta}%" : 'N/A',        'pilar' => 'Seguridad', 'peso' => 15,  'emoji' => '✅', 'titulo' => 'OWD Ruta',           'meta_desc' => 'Sin NO OK = 100% | Con NO OK = 0%'],
            'calificaciones' => ['valor' => $promedioCalif,        'label' => $promedioCalif !== null ? "{$promedioCalif}%" : 'N/A', 'pilar' => 'Seguridad', 'peso' => 10, 'emoji' => '🎓', 'titulo' => 'Calificaciones',     'meta_desc' => 'Promedio de notas por módulo'],
            'dpo' => ['valor' => $estaEnDpo ? 0.0 : 100.0, 'label' => $estaEnDpo ? '0%' : '100%',                        'pilar' => 'Gente',     'peso' => 5,   'emoji' => '📚', 'titulo' => 'DPO Academy',        'meta_desc' => 'Sin registro = 100% | En listado = 0%'],
            'ausentismo' => ['valor' => $porcentajeAusentismo, 'label' => $porcentajeAusentismo !== null ? "{$porcentajeAusentismo}%" : 'N/A', 'pilar' => 'Gente', 'peso' => 5, 'emoji' => '📅', 'titulo' => 'Ausentismo', 'meta_desc' => 'Sin incapacidad = 100%'],
            'marcaciones' => ['valor' => $tieneMalasMarcaciones ? 0.0 : 100.0, 'label' => $tieneMalasMarcaciones ? '0%' : '100%', 'pilar' => 'Gente', 'peso' => 5, 'emoji' => '🕐', 'titulo' => 'Malas Marcaciones', 'meta_desc' => 'Sin corrección = 100%'],
            'rechazos' => ['valor' => $evento?->rechazos !== null ? ((float) $evento->rechazos >= 2.4 ? 0.0 : 100.0) : null, 'label' => $evento?->rechazos !== null ? ((float) $evento->rechazos >= 2.4 ? '0%' : '100%') : 'N/A', 'pilar' => 'Reparto', 'peso' => 11, 'emoji' => '🔄', 'titulo' => 'Rechazos', 'meta_desc' => '< 2.4% rechazos = 100%'],
            'sac' => ['valor' => $casosSac === 0 ? 100.0 : 0.0, 'label' => $casosSac === 0 ? '100%' : '0%', 'pilar' => 'Reparto', 'peso' => 8, 'emoji' => '🎧', 'titulo' => 'SAC', 'meta_desc' => 'Sin casos = 100%'],
            'adherencia' => ['valor' => $evento?->adherencia_tiempo !== null ? ((float) $evento->adherencia_tiempo >= 83 ? 100.0 : 0.0) : null, 'label' => $evento?->adherencia_tiempo !== null ? ((float) $evento->adherencia_tiempo >= 83 ? '100%' : '0%') : 'N/A', 'pilar' => 'Reparto', 'peso' => 8, 'emoji' => '⏰', 'titulo' => 'Adherencia Tiempo', 'meta_desc' => '≥ 83% = 100%'],
            'rmd' => ['valor' => $evento?->rmd !== null ? ((float) $evento->rmd >= 4 ? 100.0 : 0.0) : null, 'label' => $evento?->rmd !== null ? ((float) $evento->rmd >= 4 ? '100%' : '0%') : 'N/A', 'pilar' => 'Reparto', 'peso' => 8, 'emoji' => '🏆', 'titulo' => 'RMD', 'meta_desc' => 'Promedio ≥ 4 = 100%'],
            // FLOTA — solo conductores, binario Aprobado/No Aprobado (default Aprobado)
            'cl_pre' => [
                'valor'    => $esConductorPortal ? ($clPreAprobado ? 100.0 : 0.0) : null,
                'label'    => $esConductorPortal ? ($clPreAprobado ? 'Aprobado' : 'No Aprobado') : 'N/A',
                'pilar' => 'Flota', 'peso' => 7.5, 'emoji' => '🔍', 'titulo' => 'Checklist Pre',
                'meta_desc' => 'Solo conductores · Default Aprobado (100%)',
            ],
            'cl_post' => [
                'valor'    => $esConductorPortal ? ($clPostAprobado ? 100.0 : 0.0) : null,
                'label'    => $esConductorPortal ? ($clPostAprobado ? 'Aprobado' : 'No Aprobado') : 'N/A',
                'pilar' => 'Flota', 'peso' => 7.5, 'emoji' => '🏁', 'titulo' => 'Checklist Post',
                'meta_desc' => 'Solo conductores · Default Aprobado (100%)',
            ],
        ];

        return Inertia::render('colaborador/mi-plan-premiacion/index', [
            'colaborador' => [
                'id' => $colaborador->id,
                'nombre_completo' => $colaborador->nombre_completo,
                'cedula' => $colaborador->cedula,
                'cargo' => $colaborador->cargo ?? 'Sin cargo',
                'area' => $colaborador->area ?? 'General',
                'imagen' => $colaborador->imagen ?? null,
                'aci_realizadas' => $aciRealizadas,
            ],
            'metricas' => $metricas,
            'historial_aci' => $historialAci,
            'mes' => $mes,
            'anio' => $anio,
            'umbral_checklist' => $umbralCl,
        ]);
    }

    public function misIncentivos(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $mes = $request->get('mes');

        $meses = Incentivo::where('colaborador_id', $colaborador->id)
            ->whereNotNull('mes')
            ->distinct()
            ->orderBy('mes')
            ->pluck('mes');

        $incentivos = Incentivo::where('colaborador_id', $colaborador->id)
            ->when($mes, fn ($q) => $q->where('mes', $mes))
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('colaborador/incentivos/index', [
            'colaborador' => [
                'id'              => $colaborador->id,
                'nombre_completo' => $colaborador->nombre_completo,
                'cedula'          => $colaborador->cedula,
                'cargo'           => $colaborador->cargo ?? 'Sin cargo',
                'area'            => $colaborador->area ?? '',
                'imagen'          => $colaborador->imagen ?? null,
            ],
            'incentivos' => $incentivos,
            'meses'      => $meses,
            'mes'        => $mes ?? '',
        ]);
    }

    public function miVariable(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $mes = $request->get('mes');

        $meses = Incentivo::where('colaborador_id', $colaborador->id)
            ->whereNotNull('mes')
            ->distinct()
            ->orderBy('mes')
            ->pluck('mes');

        $incentivos = Incentivo::where('colaborador_id', $colaborador->id)
            ->when($mes, fn ($q) => $q->where('mes', $mes))
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('colaborador/variable/index', [
            'colaborador' => [
                'id'              => $colaborador->id,
                'nombre_completo' => $colaborador->nombre_completo,
                'cedula'          => $colaborador->cedula,
                'cargo'           => $colaborador->cargo ?? 'Sin cargo',
                'area'            => $colaborador->area ?? '',
                'imagen'          => $colaborador->imagen ?? null,
            ],
            'incentivos' => $incentivos,
            'meses'      => $meses,
            'mes'        => $mes ?? '',
        ]);
    }

    /**
     * Vista de autoservicio "Mi Plan Padrinos": seguimiento de pruebas (con
     * evidencias, solo lectura) e historial de parejas padrino/apadrinado.
     */
    public function miPlanPadrinos(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $hoy = Carbon::today();
        $fechaIngreso = $colaborador->contrato_fecha_desde ?? $colaborador->fecha_ingreso_empresa;

        $etapasConfig = ['7_dias' => 8, '30_dias' => 30, '90_dias' => 90];
        $etapas = [];

        foreach ($etapasConfig as $etapaKey => $dias) {
            $fechaPrueba = $fechaIngreso?->copy()->addDays($dias);
            $record      = $colaborador->pruebasPeriodo()
                ->with('evidencias')
                ->where('etapa', $etapaKey)
                ->first();

            $etapas[$etapaKey] = [
                'label'                  => str_replace('_', ' ', ucfirst($etapaKey)),
                'fecha_prueba_formateada' => $fechaPrueba?->format('d/m/Y') ?? '—',
                'aplica'                 => $fechaIngreso && $hoy->greaterThanOrEqualTo($fechaPrueba),
                'estado'                 => $record?->realizada ? 'realizada' : ($fechaIngreso && $hoy->greaterThanOrEqualTo($fechaPrueba) ? 'pendiente' : 'no_aplica'),
                'fecha_realizacion'      => $record?->fecha_realizacion?->format('d/m/Y'),
                'realizado_por'          => $record?->realizadoPor?->name,
                'evidencias'             => $record?->evidencias->map(fn ($e) => [
                    'id'  => $e->id,
                    'url' => '/storage/' . $e->path,
                ])->values()->all() ?? [],
            ];
        }

        // ── Historial Plan Padrino ────────────────────────────────────────────
        $hoyCarbon = Carbon::today();

        // Como PADRINO
        $historialComoPadrino = DB::table('colaborador_padrino_historial')
            ->where('padrino_id', $colaborador->id)
            ->orderBy('fecha_inicio')
            ->get();

        $apadrinadosMap = Colaborador::query()
            ->whereIn('id', $historialComoPadrino->pluck('colaborador_id'))
            ->with(['pruebasPeriodo.evidencias'])
            ->get(['id', 'nombres', 'apellidos', 'cargo', 'imagen'])
            ->keyBy('id');

        $apadrinadosLista = $historialComoPadrino->map(function ($h) use ($hoyCarbon, $apadrinadosMap) {
            $inicio = Carbon::parse($h->fecha_inicio);
            $fin    = $h->fecha_fin ? Carbon::parse($h->fecha_fin) : $hoyCarbon;
            $apad   = $apadrinadosMap->get($h->colaborador_id);

            // Evidencias agrupadas por etapa del apadrinado
            $evidenciasPorEtapa = [];
            if ($apad) {
                foreach ($apad->pruebasPeriodo as $prueba) {
                    $evidenciasPorEtapa[$prueba->etapa] = $prueba->evidencias->map(fn ($e) => [
                        'id'  => $e->id,
                        'url' => '/storage/' . $e->path,
                    ])->values()->all();
                }
            }

            return [
                'id'              => $h->colaborador_id,
                'nombre_completo' => $apad ? $apad->nombre_completo : 'Desconocido',
                'cargo'           => $apad ? ($apad->cargo ?? 'Sin cargo') : '—',
                'imagen'          => $apad ? $apad->imagen : null,
                'fecha_inicio'    => $inicio->format('d/m/Y'),
                'fecha_fin'       => $h->fecha_fin ? $fin->format('d/m/Y') : null,
                'dias'            => (int) $inicio->diffInDays($fin),
                'activo'          => $h->fecha_fin === null,
                'evidencias'      => $evidenciasPorEtapa, // ['7_dias' => [...], '30_dias' => [...], '90_dias' => [...]]
            ];
        })->values()->all();

        $diasTotalPadrino = collect($apadrinadosLista)->sum('dias');

        // Como APADRINADO
        $historialComoApadrinado = DB::table('colaborador_padrino_historial')
            ->where('colaborador_id', $colaborador->id)
            ->orderBy('fecha_inicio')
            ->get();

        $padrinosMap = Colaborador::query()
            ->whereIn('id', $historialComoApadrinado->pluck('padrino_id'))
            ->get(['id', 'nombres', 'apellidos', 'cargo', 'imagen'])
            ->keyBy('id');

        // Evidencias propias del colaborador (como apadrinado) ya calculadas en $etapas
        $misEvidencias = [];
        foreach ($etapas as $etapaKey => $etapaData) {
            if (!empty($etapaData['evidencias'])) {
                $misEvidencias[$etapaKey] = $etapaData['evidencias'];
            }
        }

        $historialApadrinadoLista = $historialComoApadrinado->map(function ($h) use ($hoyCarbon, $padrinosMap, $misEvidencias) {
            $inicio = Carbon::parse($h->fecha_inicio);
            $fin    = $h->fecha_fin ? Carbon::parse($h->fecha_fin) : $hoyCarbon;
            $pad    = $padrinosMap->get($h->padrino_id);
            return [
                'padrino_id'     => $h->padrino_id,
                'padrino_nombre' => $pad ? $pad->nombre_completo : 'Desconocido',
                'padrino_cargo'  => $pad ? ($pad->cargo ?? 'Sin cargo') : '—',
                'padrino_imagen' => $pad ? $pad->imagen : null,
                'fecha_inicio'   => $inicio->format('d/m/Y'),
                'fecha_fin'      => $h->fecha_fin ? $fin->format('d/m/Y') : null,
                'dias'           => (int) $inicio->diffInDays($fin),
                'activo'         => $h->fecha_fin === null,
                'evidencias'     => $misEvidencias, // mis propias evidencias como apadrinado
            ];
        })->values()->all();

        // Tiempo legible
        $tiempoTexto = 'Sin actividad';
        if ($diasTotalPadrino > 0) {
            $years  = intdiv($diasTotalPadrino, 365);
            $months = intdiv($diasTotalPadrino % 365, 30);
            $days   = $diasTotalPadrino % 365 % 30;
            $partes = [];
            if ($years > 0)  $partes[] = "{$years} " . ($years  === 1 ? 'año'  : 'años');
            if ($months > 0) $partes[] = "{$months} " . ($months === 1 ? 'mes'  : 'meses');
            if ($days > 0)   $partes[] = "{$days} "   . ($days   === 1 ? 'día'  : 'días');
            $tiempoTexto = implode(', ', $partes);
        }

        $diasEnEmpresa = $fechaIngreso ? (int) $fechaIngreso->diffInDays($hoyCarbon) : 0;

        return Inertia::render('colaborador/mi-plan-padrinos/index', [
            'colaborador' => [
                'id'                   => $colaborador->id,
                'nombre_completo'      => $colaborador->nombre_completo,
                'cedula'               => $colaborador->cedula,
                'cargo'                => $colaborador->cargo ?? '—',
                'area'                 => $colaborador->area ?? '—',
                'turno'                => $colaborador->turno ?? '—',
                'imagen'               => $colaborador->imagen,
                'fecha_ingreso'        => $fechaIngreso?->format('d/m/Y') ?? '—',
                'es_padrino'           => $colaborador->es_padrino === 'aplica',
                'tipo_padrino'         => $colaborador->tipo_padrino,
                // historial
                'fecha_ingreso_texto'  => $fechaIngreso?->format('d/m/Y') ?? 'N/R',
                'dias_en_empresa'      => $diasEnEmpresa,
                'veces_padrino'        => $historialComoPadrino->count(),
                'dias_total_padrino'   => $diasTotalPadrino,
                'tiempo_total_texto'   => $tiempoTexto,
                'apadrinados_lista'    => $apadrinadosLista,
                'veces_apadrinado'     => $historialComoApadrinado->count(),
                'historial_apadrinado' => $historialApadrinadoLista,
                'correo'               => $colaborador->correo,
                'celular'              => $colaborador->celular_1,
                'mensaje_padrino'      => $colaborador->mensaje_padrino,
            ],
            'etapas' => $etapas,
        ]);
    }

    private function colaboradorDe(Request $request): ?Colaborador
    {
        return $request->user()->colaborador;
    }

    private function colaboradorDeOFallar(Request $request): Colaborador
    {
        return $this->colaboradorDe($request) ?? abort(
            403,
            'Tu cuenta todavía no está vinculada a un registro de colaborador. Contacta a un administrador.'
        );
    }
}
