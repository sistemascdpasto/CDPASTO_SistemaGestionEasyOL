<?php

namespace App\Http\Controllers\Colaborador;

use App\Http\Controllers\Controller;
use App\Models\Reparto\CompensacionVariable;
use App\Models\Reparto\CompensacionVariableDiaria;
use App\Models\Seguridad\Colaborador;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class CompensacionColaboradorController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        
        // Obtener el colaborador asociado al usuario
        $colaborador = Colaborador::where('user_id', $user->id)->first();
        
        if (!$colaborador) {
            return Inertia::render('colaborador/mi-compensacion/index', [
                'colaborador' => null,
                'registros' => [],
                'fecha_seleccionada' => null,
                'registro_dia' => null,
                'error' => 'No se encontró información del colaborador.',
            ]);
        }

        // Obtener la fecha seleccionada (por defecto hoy)
        $fechaSeleccionada = $request->input('fecha', date('Y-m-d'));
        
        // Obtener todos los registros del colaborador (últimos 90 días)
        $registros = CompensacionVariableDiaria::where('cedula', $colaborador->cedula)
            ->where('fecha', '>=', now()->subDays(90))
            ->orderBy('fecha', 'desc')
            ->get(['id', 'fecha', 'rechazos', 'valor_x_dia', 'valor_var', 'valor_perdido', 'porcentaje_variable', 'cal_rechazos', 'cal_rechazos_2', 'meta_1', 'meta_2'])
            ->map(function ($registro) {
                return [
                    'id' => $registro->id,
                    'fecha' => $registro->fecha instanceof \DateTimeInterface ? $registro->fecha->format('Y-m-d') : $registro->fecha,
                    'rechazos' => (float) ($registro->rechazos ?? 0),
                    'valor_x_dia' => (float) ($registro->valor_x_dia ?? 0),
                    'valor_var' => (float) ($registro->valor_var ?? 0),
                    'valor_perdido' => (float) ($registro->valor_perdido ?? 0),
                    'porcentaje_variable' => $registro->porcentaje_variable ?? '0%',
                    'cal_rechazos' => (float) ($registro->cal_rechazos ?? 0),
                    'cal_rechazos_2' => (float) ($registro->cal_rechazos_2 ?? 0),
                    'meta_1' => (float) ($registro->meta_1 ?? 2.1),
                    'meta_2' => (float) ($registro->meta_2 ?? 2.6),
                ];
            });

        // Obtener el registro del día seleccionado
        $registroDia = CompensacionVariableDiaria::where('cedula', $colaborador->cedula)
            ->whereDate('fecha', $fechaSeleccionada)
            ->first();

        $registroDiaFormateado = null;
        if ($registroDia) {
            // Calcular rechazos en formato porcentaje para mostrar
            $rechazosRaw = (float) ($registroDia->rechazos ?? 0);
            $rechazosPct = $rechazosRaw > 1 ? $rechazosRaw : $rechazosRaw * 100; // Convertir a %
            
            $registroDiaFormateado = [
                'id' => $registroDia->id,
                'fecha' => $registroDia->fecha instanceof \DateTimeInterface ? $registroDia->fecha->format('Y-m-d') : $registroDia->fecha,
                'rechazos' => $rechazosRaw,
                'rechazos_porcentaje' => round($rechazosPct, 2),
                'valor_x_dia' => (float) ($registroDia->valor_x_dia ?? 0),
                'valor_var' => (float) ($registroDia->valor_var ?? 0),
                'valor_perdido' => (float) ($registroDia->valor_perdido ?? 0),
                'porcentaje_variable' => $registroDia->porcentaje_variable ?? '0%',
                'porcentaje_variable_no_cum' => $registroDia->porcentaje_variable_no_cum ?? '0%',
                'cal_rechazos' => (float) ($registroDia->cal_rechazos ?? 0),
                'cal_rechazos_2' => (float) ($registroDia->cal_rechazos_2 ?? 0),
                'meta_1' => (float) ($registroDia->meta_1 ?? 2.1),
                'meta_2' => (float) ($registroDia->meta_2 ?? 2.6),
                'placa' => $registroDia->placa,
                'transporte' => $registroDia->transporte,
                'nombre_completo' => $registroDia->nombre_completo,
                'cargo' => $registroDia->cargo,
            ];
        }

        // Calcular estadísticas del mes de la fecha seleccionada (no siempre el mes actual)
        $mesSeleccionadoYm = substr($fechaSeleccionada, 0, 7); // "2026-04"
        $estadisticasMes = CompensacionVariableDiaria::where('cedula', $colaborador->cedula)
            ->whereRaw("DATE_FORMAT(fecha, '%Y-%m') = ?", [$mesSeleccionadoYm])
            ->selectRaw('
                COUNT(*) as dias_trabajados,
                SUM(valor_var) as total_ganado,
                SUM(valor_perdido) as total_perdido,
                AVG(rechazos) as promedio_rechazos,
                SUM(CASE WHEN rechazos <= 0.021 THEN 1 ELSE 0 END) as dias_meta_1,
                SUM(CASE WHEN rechazos <= 0.026 THEN 1 ELSE 0 END) as dias_meta_2
            ')
            ->first();

        // ── Ausencias del mes actual desde compensaciones_variables ──────────
        // La tabla usa "identificador" (no cedula) y "mes" como string del nombre del mes
        $anioActual = (int) date('Y');
        $mesActual  = (int) date('n');

        $mesesEs = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $mesActualNombre = $mesesEs[$mesActual];

        $registroMensual = CompensacionVariable::where('identificador', $colaborador->cedula)
            ->where('anio', $anioActual)
            ->where('mes', $mesActualNombre)
            ->first();

        $ausencias = [
            'justificada'   => (float) ($registroMensual?->ausencia_justificada ?? 0),
            'injustificada' => (float) ($registroMensual?->ausencia_injustificada ?? 0),
        ];

        // ── Historial anual enero–diciembre ───────────────────────────────────

        // Estadísticas mensuales agrupadas desde compensaciones_variables_diarias
        $historialRaw = CompensacionVariableDiaria::where('cedula', $colaborador->cedula)
            ->where('anio', $anioActual)
            ->selectRaw('
                MONTH(fecha) as mes_num,
                COUNT(*) as dias_trabajados,
                SUM(valor_var) as total_ganado,
                SUM(valor_perdido) as total_perdido,
                AVG(rechazos) as promedio_rechazos,
                SUM(CASE WHEN rechazos <= 0.021 THEN 1 ELSE 0 END) as dias_meta_1,
                SUM(CASE WHEN rechazos <= 0.026 THEN 1 ELSE 0 END) as dias_meta_2
            ')
            ->groupBy('mes_num')
            ->orderBy('mes_num')
            ->get()
            ->keyBy('mes_num');

        // Ausencias mensuales desde compensaciones_variables (mes como string)
        $ausenciasAnualesRaw = CompensacionVariable::where('identificador', $colaborador->cedula)
            ->where('anio', $anioActual)
            ->select('mes', 'ausencia_justificada', 'ausencia_injustificada')
            ->get();

        // Mapear nombre de mes → número para poder hacer keyBy numérico
        $mesesEsFlip = array_flip(array_map('strtolower', $mesesEs)); // ['enero'=>1, ...]
        $ausenciasAnuales = $ausenciasAnualesRaw->keyBy(function ($row) use ($mesesEsFlip) {
            return $mesesEsFlip[strtolower(trim($row->mes ?? ''))] ?? 0;
        });

        $historialAnual = collect(range(1, 12))->map(function ($mesNum) use ($mesesEs, $historialRaw, $ausenciasAnuales, $mesActual) {
            $stats   = $historialRaw->get($mesNum);
            $ausRow  = $ausenciasAnuales->get($mesNum);
            $esFuturo = $mesNum > $mesActual;

            return [
                'mes_num'          => $mesNum,
                'mes_nombre'       => $mesesEs[$mesNum],
                'es_futuro'        => $esFuturo,
                'dias_trabajados'  => $esFuturo ? null : (int) ($stats?->dias_trabajados ?? 0),
                'total_ganado'     => $esFuturo ? null : round((float) ($stats?->total_ganado ?? 0), 2),
                'total_perdido'    => $esFuturo ? null : round((float) ($stats?->total_perdido ?? 0), 2),
                'promedio_rechazos'=> $esFuturo ? null : round((float) ($stats?->promedio_rechazos ?? 0) * 100, 2),
                'dias_meta_1'      => $esFuturo ? null : (int) ($stats?->dias_meta_1 ?? 0),
                'dias_meta_2'      => $esFuturo ? null : (int) ($stats?->dias_meta_2 ?? 0),
                'aus_justificada'  => $esFuturo ? null : (float) ($ausRow?->ausencia_justificada ?? 0),
                'aus_injustificada'=> $esFuturo ? null : (float) ($ausRow?->ausencia_injustificada ?? 0),
            ];
        })->values()->toArray();

        return Inertia::render('colaborador/mi-compensacion/index', [
            'colaborador' => [
                'cedula' => $colaborador->cedula,
                'nombre_completo' => trim($colaborador->nombres . ' ' . $colaborador->apellidos),
                'cargo' => $colaborador->cargo,
            ],
            'registros' => $registros,
            'fecha_seleccionada' => $fechaSeleccionada,
            'registro_dia' => $registroDiaFormateado,
            'estadisticas_mes' => [
                'dias_trabajados'   => $estadisticasMes->dias_trabajados ?? 0,
                'total_ganado'      => round((float) ($estadisticasMes->total_ganado ?? 0), 2),
                'total_perdido'     => round((float) ($estadisticasMes->total_perdido ?? 0), 2),
                'promedio_rechazos' => round((float) ($estadisticasMes->promedio_rechazos ?? 0), 2),
                'dias_meta_1'       => $estadisticasMes->dias_meta_1 ?? 0,
                'dias_meta_2'       => $estadisticasMes->dias_meta_2 ?? 0,
            ],
            'ausencias'      => $ausencias,
            'historial_anual'=> $historialAnual,
            'error' => null,
        ]);
    }
}
