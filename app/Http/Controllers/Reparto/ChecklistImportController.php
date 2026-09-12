<?php

namespace App\Http\Controllers\Reparto;

use App\Http\Controllers\Controller;
use App\Models\Reparto\ChecklistVehiculo;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ChecklistImportController extends Controller
{
    // ── Mapa flexible: variantes del encabezado del Excel → campo del modelo ──
    // Clave   = versión normalizada (minúsculas, sin espacios extra)
    // Valor   = campo en la BD
    private const COLUMN_MAP = [
        // Identificación
        'id_form'               => 'id_form',
        'idform'                => 'id_form',
        'id form'               => 'id_form',
        'estado'                => 'estado',
        'esado_form'            => 'estado_form',   // typo real del Excel
        'estado_form'           => 'estado_form',
        'estadoform'            => 'estado_form',
        'fecha'                 => 'fecha',
        'fecha_fin'             => 'fecha_fin',
        'fechafin'              => 'fecha_fin',
        'id_centro'             => 'id_centro',
        'idcentro'              => 'id_centro',
        'id_regional'           => 'id_regional',
        'idregional'            => 'id_regional',
        'regional'              => 'regional',
        'centro'                => 'centro',
        'operacion'             => 'operacion',
        'operación'             => 'operacion',

        // Conductor / vehículo
        'cedula_conductor'      => 'cedula_conductor',
        'cedulaconductor'       => 'cedula_conductor',
        'cedula conductor'      => 'cedula_conductor',
        'placa_vehiculo'        => 'placa_vehiculo',
        'placavehiculo'         => 'placa_vehiculo',
        'placa vehiculo'        => 'placa_vehiculo',
        'placa'                 => 'placa_vehiculo',
        'odometro'              => 'odometro',
        'odómetro'              => 'odometro',

        // Condiciones generales
        'salud_descanso'        => 'salud_descanso',
        'saluddescanso'         => 'salud_descanso',
        'libremedicamentos'     => 'libre_medicamentos',
        'libre_medicamentos'    => 'libre_medicamentos',
        'libre medicamentos'    => 'libre_medicamentos',
        'fugas'                 => 'fugas',
        'testigospresionaire'   => 'testigos_presion_aire',
        'testigos_presion_aire' => 'testigos_presion_aire',
        'testigos presion aire' => 'testigos_presion_aire',
        'frenoparqueo'          => 'freno_parqueo',
        'freno_parqueo'         => 'freno_parqueo',
        'freno parqueo'         => 'freno_parqueo',
        'kitreparto'            => 'kit_reparto',
        'kit_reparto'           => 'kit_reparto',
        'kit reparto'           => 'kit_reparto',
        'inventario'            => 'inventario',
        'capacidadvehiculo'     => 'capacidad_vehiculo',
        'capacidad_vehiculo'    => 'capacidad_vehiculo',
        'condiciones_operar'    => 'condiciones_operar',
        'condicionesoperar'     => 'condiciones_operar',
        'docuentos_operar'      => 'documentos_operar',   // typo real
        'documentos_operar'     => 'documentos_operar',
        'licencia_vigente'      => 'licencia_vigente',
        'licenciavigente'       => 'licencia_vigente',
        'licencia_original'     => 'licencia_original',
        'licenciaoriginal'      => 'licencia_original',
        'tecnomecanica'         => 'tecnomecanica',
        'tecnomecánica'         => 'tecnomecanica',
        'soat_vigente'          => 'soat_vigente',
        'soatvigente'           => 'soat_vigente',

        // Kit emergencia
        'kit_totalidad'         => 'kit_totalidad',
        'kittotalidad'          => 'kit_totalidad',
        'repuestosbuen_estado'  => 'repuestos_buen_estado',   // typo real
        'repuestos_buen_estado' => 'repuestos_buen_estado',
        'extintor'              => 'extintor',
        'extintorvigente'       => 'extintor_vigente',
        'extintor_vigente'      => 'extintor_vigente',
        'botiquincondiciones'   => 'botiquin_condiciones',
        'botiquin_condiciones'  => 'botiquin_condiciones',
        'linternacondiciones'   => 'linterna_condiciones',
        'linterna_condiciones'  => 'linterna_condiciones',
        'kitbasico'             => 'kit_basico',
        'kit_basico'            => 'kit_basico',
        'kitbásico'             => 'kit_basico',

        // Niveles
        'niveles_totalidad'     => 'niveles_totalidad',
        'nivelestotalidad'      => 'niveles_totalidad',
        'cumbustiblle_suficiente' => 'combustible_suficiente',  // typo real
        'combustible_suficiente'=> 'combustible_suficiente',
        'nivel_combustible'     => 'nivel_combustible',
        'nivelcombustible'      => 'nivel_combustible',
        'liquido_embrague'      => 'liquido_embrague',
        'liquidoembrague'       => 'liquido_embrague',
        'refrigerante_estado'   => 'refrigerante_estado',
        'refrigeranteestado'    => 'refrigerante_estado',
        'aceite_estado'         => 'aceite_estado',
        'aceiteestado'          => 'aceite_estado',
        'estado_hidraulico'     => 'estado_hidraulico',
        'estadohidraulico'      => 'estado_hidraulico',
        'aceite_caja'           => 'aceite_caja',
        'aceitecaja'            => 'aceite_caja',
        'agua_limpiabrisas'     => 'agua_limpiabrisas',
        'agualimpiabrisas'      => 'agua_limpiabrisas',

        // Llantas
        'cumple_llantas'        => 'cumple_llantas',
        'cumplellantas'         => 'cumple_llantas',
        'bandas_rodamientos'    => 'bandas_rodamientos',
        'bandasrodamientos'     => 'bandas_rodamientos',
        'deformaciones_costados'=> 'deformaciones_costados',
        'labrado_profundidad'   => 'labrado_profundidad',

        // Visibilidad
        'cumple_visibilidad'    => 'cumple_visibilidad',
        'estado_panoramico'     => 'estado_panoramico',
        'estado_retrovisores'   => 'estado_retrovisores',
        'estado_limpiabrisas'   => 'estado_limpiabrisas',
        'estado_cinturones'     => 'estado_cinturones',
        'estado_colapies'       => 'estado_colapies',
        'cerrar_fuera'          => 'cerrar_fuera',
        'estado_dashcam'        => 'estado_dashcam',
        'estado_vidrios'        => 'estado_vidrios',

        // Luces
        'cumple_luces'          => 'cumple_luces',
        'luces_freno'           => 'luces_freno',
        'estado_principales'    => 'estado_principales',
        'luces_reserva'         => 'luces_reserva',
        'luces_direccionales'   => 'luces_direccionales',
        'luces_estacionarias'   => 'luces_estacionarias',
        'luces_laterales'       => 'luces_laterales',

        // Bocina
        'estado_pito'           => 'estado_pito',
        'estado_pitoreserva'    => 'estado_pito_reserva',   // typo real
        'estado_pito_reserva'   => 'estado_pito_reserva',
        'cumple_audible'        => 'cumple_audible',

        // Carrocería
        'cumple_carroceria'     => 'cumple_carroceria',
        'estado_correas'        => 'estado_correas',
        'estado_parales'        => 'estado_parales',
        'estado_cortinas'       => 'estado_cortinas',
        'estado_chapas'         => 'estado_chapas',

        // Carretilla
        'cumple_carretilla'     => 'cumple_carretilla',
        'cuenta_etiqueta'       => 'cuenta_etiqueta',
        'llantas_rodamientosdos'  => 'llantas_rodamientos_dos',
        'llantas_rodamientos_dos' => 'llantas_rodamientos_dos',
        'estado_carretillados'    => 'estado_carretilla_dos',
        'estado_carretilla_dos'   => 'estado_carretilla_dos',
        'carretillados'           => 'carretilla_dos',
        'carretilla_dos'          => 'carretilla_dos',
        'etiqueta'                => 'etiqueta',
        'estado_rodamiento'       => 'estado_rodamiento',
        'estado_carretillauno'    => 'estado_carretilla_uno',
        'estado_carretilla_uno'   => 'estado_carretilla_uno',
        'carretilla1'             => 'carretilla_uno',
        'carretilla_uno'          => 'carretilla_uno',

        // Cierre
        'obervaciones'          => 'observaciones',   // typo real
        'observaciones'         => 'observaciones',
        'firma_conductor'       => 'firma_conductor',
        'conductor_operar'      => 'conductor_operar',
        'vehiculo_operar'       => 'vehiculo_operar',
        'vehiculo_bitren'       => 'vehiculo_bitren',
        'estado_bitren'         => 'estado_bitren',
        'nombre_flota'          => 'nombre_flota',
        'apellido_flota'        => 'apellido_flota',
        'firma_responsable'     => 'firma_responsable',

        // Métricas
        'cumpl'                 => 'cumpl',
        'cumpl %'               => 'cumpl',   // "CUMPL %" del Excel
        'cumpl%'                => 'cumpl',
        '% cumpl'               => 'cumpl',
        '%cumpl'                => 'cumpl',
        'meta td'               => 'meta_td',
        'meta_td'               => 'meta_td',
        'tiempo de ejecución'   => 'tiempo_ejecucion',
        'tiempo de ejecucion'   => 'tiempo_ejecucion',
        'tiempo_ejecucion'      => 'tiempo_ejecucion',
        'mes'                   => 'mes',
        'semana'                => 'semana',
        'año'                   => 'anio',
        'ano'                   => 'anio',
        'anio'                  => 'anio',
        'dia'                   => 'dia',
        'día'                   => 'dia',
        'meta'                  => 'meta',
        'cumpl meta'            => 'cumpl_meta',   // "CUMPL Meta" del Excel
        'cumpl_meta'            => 'cumpl_meta',
        'cumplmeta'             => 'cumpl_meta',   // sin espacio
        'cumpl% meta'           => 'cumpl_meta',
        '% cumpl meta'          => 'cumpl_meta',
        'cumpl. meta'           => 'cumpl_meta',
        'cumplimiento meta'     => 'cumpl_meta',
        'cumplimientometa'      => 'cumpl_meta',
        'codigo_responsable'    => 'codigo_responsable',
        'codigoresponsable'     => 'codigo_responsable',
        'codigo responsable'    => 'codigo_responsable',
    ];

    // ── Mapa secundario: encabezados que aparecen DOS VECES en el Excel ───
    // Cuando un campo ya fue asignado, se usa este mapa para la segunda aparición.
    // Ej: "CUMPL" o "CUMPL %" aparece dos veces → primera=cumpl, segunda=cumpl_meta
    private const COLUMN_MAP_SECONDARY = [
        'cumpl'   => 'cumpl_meta',
        'cumpl %' => 'cumpl_meta',
        'cumpl%'  => 'cumpl_meta',
        '% cumpl' => 'cumpl_meta',
        '%cumpl'  => 'cumpl_meta',
    ];

    // ── Campos fecha que necesitan conversión ──────────────────────────────
    private const DATE_FIELDS = ['fecha', 'fecha_fin'];

    // ── Horas/duraciones: Excel las guarda como fracción de día ────────────
    // Ej: 00:04:00 → 0.002777... → se convierte a "00:04:00" con ExcelDate
    private const TIME_FIELDS = ['meta_td', 'tiempo_ejecucion', 'meta'];

    // ── Porcentajes de Excel: Excel guarda 100% como 1.0 → multiplicar ×100 ─
    // meta_td ya NO está aquí — es duración, no porcentaje
    private const PCT_FIELDS = ['cumpl', 'cumpl_meta'];

    // ── Campos enteros (excepto mes que puede venir como texto) ────────────
    private const INT_FIELDS = ['semana', 'anio', 'dia'];

    // ── Mapa de nombres de mes en español/inglés → número ─────────────────
    private const MESES_MAP = [
        'enero'=>1,'january'=>1,'jan'=>1,'ene'=>1,
        'febrero'=>2,'february'=>2,'feb'=>2,
        'marzo'=>3,'march'=>3,'mar'=>3,
        'abril'=>4,'april'=>4,'abr'=>4,'apr'=>4,
        'mayo'=>5,'may'=>5,
        'junio'=>6,'june'=>6,'jun'=>6,
        'julio'=>7,'july'=>7,'jul'=>7,
        'agosto'=>8,'august'=>8,'ago'=>8,'aug'=>8,
        'septiembre'=>9,'september'=>9,'sep'=>9,'sept'=>9,
        'octubre'=>10,'october'=>10,'oct'=>10,
        'noviembre'=>11,'november'=>11,'nov'=>11,
        'diciembre'=>12,'december'=>12,'dic'=>12,'dec'=>12,
    ];

    // ── Campos que son numéricos pero se guardan como string limpio ────────
    // (odómetro puede venir como 125430.0 desde Excel y debe quedar "125430")
    // id_form también puede venir como 1722822.0 desde Excel
    private const NUMERIC_STRING_FIELDS = ['odometro', 'id_form'];

    // ─────────────────────────────────────────────────────────────────────────

    public function index(Request $request): Response
    {
        $query = ChecklistVehiculo::query();

        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->input('fecha_desde'));
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->input('fecha_hasta'));
        }
        if ($request->filled('placa')) {
            $query->where('placa_vehiculo', strtoupper($request->input('placa')));
        }
        if ($request->filled('cedula')) {
            $query->where('cedula_conductor', $request->input('cedula'));
        }

        $registros = $query->orderByDesc('fecha')->paginate(50)->withQueryString();

        $placas = ChecklistVehiculo::whereNotNull('placa_vehiculo')
            ->distinct()->orderBy('placa_vehiculo')->pluck('placa_vehiculo');

        return Inertia::render('reparto/checklist/import', [
            'registros' => $registros,
            'placas'    => $placas,
            'filters'   => [
                'fecha_desde' => $request->input('fecha_desde', ''),
                'fecha_hasta' => $request->input('fecha_hasta', ''),
                'placa'       => $request->input('placa', ''),
                'cedula'      => $request->input('cedula', ''),
            ],
            'flash'      => session()->only(['success', 'error']),
            'duplicados' => session('duplicados', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        set_time_limit(300);
        ini_set('memory_limit', '512M');

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ], [
            'archivo.required' => 'Debes seleccionar un archivo.',
            'archivo.mimes'    => 'El archivo debe ser Excel (.xlsx, .xls) o CSV.',
            'archivo.max'      => 'El archivo no puede superar 20 MB.',
        ]);

        try {
            $path   = $request->file('archivo')->getPathname();
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestDataRow();
            $highestCol  = $sheet->getHighestDataColumn();

            // ── 1. Leer y normalizar encabezados ──────────────────────────
            $rawHeaders            = [];
            $fieldMap              = [];
            $encabezadosNoMapeados = [];

            foreach ($sheet->getRowIterator(1, 1) as $row) {
                $ci = 0;
                // Rastrea campos ya asignados para manejar encabezados duplicados
                // (ej: "CUMPL" aparece dos veces → primera=cumpl, segunda=cumpl_meta)
                $camposAsignados = [];
                foreach ($row->getCellIterator('A', $highestCol) as $cell) {
                    $raw             = trim((string) $cell->getValue());
                    $rawHeaders[$ci] = $raw;
                    $norm            = $this->normalizeHeader($raw);

                    // Paso 1: buscar en el mapa primario
                    $field = self::COLUMN_MAP[$norm] ?? null;

                    // Paso 2: si no hay coincidencia exacta, intentar fuzzy
                    if ($field === null && $raw !== '') {
                        $field = $this->fuzzyMatchHeader($norm);
                    }

                    // Paso 3: si el campo ya fue asignado (encabezado duplicado),
                    // buscar en el mapa secundario usando el norm como clave.
                    // Ejemplo: dos columnas "CUMPL" → primera=cumpl, segunda=cumpl_meta
                    if ($field !== null && in_array($field, $camposAsignados, true)) {
                        $field = self::COLUMN_MAP_SECONDARY[$norm] ?? null;
                        // Si el secundario tampoco está libre, ignorar la columna
                        if ($field !== null && in_array($field, $camposAsignados, true)) {
                            $field = null;
                        }
                    }

                    $fieldMap[$ci] = $field;

                    if ($field !== null) {
                        $camposAsignados[] = $field;
                    } elseif ($raw !== '') {
                        $encabezadosNoMapeados[] = [
                            'columna'     => \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1),
                            'encabezado'  => $raw,
                            'normalizado' => $norm,
                        ];
                    }
                    $ci++;
                }
            }

            Log::info('[Checklist] Headers leídos: ' . json_encode($rawHeaders));
            // Log detallado: columna Excel → raw → normalizado → campo BD
            $mapeoLog = [];
            foreach ($rawHeaders as $ci => $raw) {
                $mapeoLog[] = sprintf(
                    'Col %s | raw="%s" | norm="%s" | campo=%s',
                    \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci + 1),
                    $raw,
                    $this->normalizeHeader($raw),
                    $fieldMap[$ci] ?? '(sin mapeo)'
                );
            }
            Log::info('[Checklist] Mapeo headers→campos:' . "\n" . implode("\n", $mapeoLog));
            if (!empty($encabezadosNoMapeados)) {
                Log::warning('[Checklist] Columnas SIN mapear: ' . json_encode($encabezadosNoMapeados));
            }

            // ── 2. Verificar que id_form esté presente ────────────────────
            if (!in_array('id_form', $fieldMap, true)) {
                return back()->withErrors([
                    'archivo' => 'El archivo no contiene la columna "id_form". Verifica que el encabezado exista.',
                ]);
            }

            // Índice de la columna id_form en el Excel
            $idFormColIdx = array_search('id_form', $fieldMap, true);

            // ── 3. Precargar id_form existentes en BD ─────────────────────
            // Misma normalización que se aplica al Excel → comparación exacta.
            $existentes = array_flip(
                ChecklistVehiculo::pluck('id_form')
                    ->map(fn ($v) => $this->normalizeIdForm($v))
                    ->filter(fn ($v) => $v !== null)
                    ->toArray()
            );

            // ── 4. Procesar filas ─────────────────────────────────────────
            $insertados    = 0;
            $duplicados    = 0;
            $errores       = 0;
            $batch         = [];
            $idsDuplicados = [];
            $now           = now()->toDateTimeString();

            for ($rowNum = 2; $rowNum <= $highestRow; $rowNum++) {

                $rowValues = [];
                $ci        = 0;
                foreach ($sheet->getRowIterator($rowNum, $rowNum) as $row) {
                    foreach ($row->getCellIterator('A', $highestCol) as $cell) {
                        $rowValues[$ci] = $cell->getValue();
                        $ci++;
                    }
                }

                // Saltar filas completamente vacías
                $allEmpty = true;
                foreach ($rowValues as $v) {
                    if ($v !== null && $v !== '') { $allEmpty = false; break; }
                }
                if ($allEmpty) continue;

                // Obtener id_form DIRECTO de la celda, sin castValue
                $idFormRaw = $rowValues[$idFormColIdx] ?? null;
                $idForm    = $this->normalizeIdForm($idFormRaw);

                if ($idForm === null) {
                    $errores++;
                    continue;
                }

                // Construir array campo => valor para todos los demás campos
                $data = ['id_form' => $idForm];
                foreach ($fieldMap as $idx => $field) {
                    if ($field === null || $field === 'id_form') continue;
                    $raw          = $rowValues[$idx] ?? null;
                    $data[$field] = $this->castValue($field, $raw);
                }

                // ── Corregir fecha usando el campo "mes" como fuente del mes ──
                // Regla: día y año vienen de la celda "Fecha",
                //        el mes viene de la celda "Mes" (nombre o número).
                // Esto evita confusión d/m vs m/d sin importar el locale del Excel.
                if (!empty($data['mes']) && !empty($data['fecha'])) {
                    $mesNum = $data['mes']; // ya convertido a entero por castValue
                    $fechaStr = $data['fecha']; // "Y-m-d H:i:s" o "Y-m-d"
                    try {
                        $carbonFecha = Carbon::parse($fechaStr);
                        if ($carbonFecha->month !== $mesNum) {
                            // Reemplazar solo el mes, conservando día y año
                            $fechaCorregida = Carbon::createFromDate(
                                $carbonFecha->year,
                                $mesNum,
                                $carbonFecha->day
                            );
                            $data['fecha'] = $fechaCorregida->format('Y-m-d H:i:s');
                        }
                    } catch (\Throwable) {
                        // Si no se puede corregir, dejar la fecha como vino
                    }
                }

                // ── Deduplicar SOLO por id_form ───────────────────────────
                if (isset($existentes[$idForm])) {
                    Log::info("[Checklist] Duplicado fila {$rowNum}: raw=" . json_encode($idFormRaw) . " normalizado='{$idForm}'");
                    $duplicados++;
                    $idsDuplicados[] = [
                        'id_form'           => $idForm,
                        'condicion'         => "id_form '{$idForm}' ya existe en la base de datos",
                        'operacion'         => $data['operacion']         ?? null,
                        'firma_responsable' => $data['firma_responsable'] ?? null,
                        'placa_vehiculo'    => $data['placa_vehiculo']    ?? null,
                        'cedula_conductor'  => $data['cedula_conductor']  ?? null,
                        'fecha'             => $data['fecha']             ?? null,
                        'regional'          => $data['regional']          ?? null,
                        'centro'            => $data['centro']            ?? null,
                    ];
                    continue;
                }

                $existentes[$idForm] = true;

                // ── Regla fallback para cumpl y cumpl_meta ────────────────
                // Si vienen vacíos del Excel, se calcula:
                //   SI(tiempo_ejecucion >= meta_td; 1; 0) × 100
                // → 100% si el conductor cumplió el tiempo, 0% si no.
                // Solo se aplica cuando hay tiempo_ejecucion y meta_td disponibles.
                $tej = $data['tiempo_ejecucion'] ?? null;
                $mtd = $data['meta_td']          ?? null;

                if ($tej !== null && $mtd !== null) {
                    $toSeg = function (string $t): int {
                        $parts = array_map('intval', explode(':', $t));
                        return ($parts[0] ?? 0) * 3600 + ($parts[1] ?? 0) * 60 + ($parts[2] ?? 0);
                    };
                    $cumplCalculado = $toSeg($tej) >= $toSeg($mtd) ? 100.0 : 0.0;

                    if (($data['cumpl'] ?? null) === null) {
                        $data['cumpl'] = $cumplCalculado;
                    }
                    if (($data['cumpl_meta'] ?? null) === null) {
                        $data['cumpl_meta'] = $cumplCalculado;
                    }
                }

                $data['created_at']  = $now;
                $data['updated_at']  = $now;
                $batch[]             = $data;
                $insertados++;

                if (count($batch) >= 200) {
                    ChecklistVehiculo::insert($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                ChecklistVehiculo::insert($batch);
            }

            $msg = "Importación completada: {$insertados} registro(s) nuevos";
            if ($duplicados) $msg .= ", {$duplicados} duplicado(s) omitido(s)";
            if ($errores)    $msg .= ", {$errores} fila(s) sin id_form omitida(s)";
            if (!empty($encabezadosNoMapeados)) {
                $cols = implode(', ', array_column($encabezadosNoMapeados, 'encabezado'));
                $msg .= ". ⚠ Columnas no reconocidas (datos no guardados): {$cols}";
            }

            return back()
                ->with('success', $msg)
                ->with('duplicados', $idsDuplicados)
                ->with('encabezadosNoMapeados', $encabezadosNoMapeados);

        } catch (\Throwable $e) {
            Log::error('[Checklist] Error importando: ' . $e->getMessage());
            return back()->withErrors([
                'archivo' => 'Error al procesar el archivo: ' . $e->getMessage(),
            ]);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Normaliza id_form como TEXTO EXACTO — nunca como número.
     * Evita falsos duplicados por floats con .0, notación científica,
     * NBSP de Excel, y ceros a la izquierda perdidos por coerción numérica.
     */
    private function normalizeIdForm(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') return null;

        if (is_float($raw)) {
            // Evita "1.6E+7" y decimales ".0" espurios
            $raw = sprintf('%.0f', $raw);
        }

        $str = (string) $raw;
        $str = str_replace("\xC2\xA0", ' ', $str); // NBSP que a veces mete Excel
        $str = trim($str);

        return $str === '' ? null : $str;
    }

    /** Normaliza un encabezado para compararlo con el mapa */
    private function normalizeHeader(string $header): string
    {
        $h = mb_strtolower(trim($header));
        // Reemplazar caracteres especiales que Excel puede insertar
        $h = str_replace(
            ["\xC2\xA0", "\r\n", "\r", "\n", "\t"],  // NBSP, saltos de línea, tabs
            [' ',          ' ',   ' ',  ' ',  ' '],
            $h
        );
        $h = str_replace(['á','é','í','ó','ú','ü','ñ'], ['a','e','i','o','u','u','n'], $h);
        $h = preg_replace('/\s+/', ' ', $h);  // colapsar espacios múltiples
        $h = trim($h);
        return $h;
    }

    /**
     * Coincidencia difusa para encabezados que no están en el mapa exacto.
     * Cubre variantes con espacios extra, caracteres raros, o combinaciones
     * no previstas como "CUMPL  Meta", "CUMPL\nMeta", "CUMPLMeta", etc.
     */
    private function fuzzyMatchHeader(string $norm): ?string
    {
        // Eliminar todos los separadores para comparar palabras juntas
        $compact = preg_replace('/[\s_\-\.%]+/', '', $norm);

        // "cumplmeta" o cualquier variante → cumpl_meta
        if ($compact === 'cumplmeta' || $compact === 'cumplimientometa') {
            return 'cumpl_meta';
        }

        // Contiene "cumpl" Y "meta" → cumpl_meta
        if (str_contains($norm, 'cumpl') && str_contains($norm, 'meta')) {
            return 'cumpl_meta';
        }

        // "cumpl %" o "cumpl%" sin "meta" → cumpl (campo principal)
        if (str_contains($compact, 'cumpl') && str_contains($norm, '%')) {
            return 'cumpl';
        }

        // "metatd", "metatd" → meta_td
        if ($compact === 'metatd' || $compact === 'metatiempodespacho') {
            return 'meta_td';
        }

        // "tiempodeejecucion", "tiempoejecucion" → tiempo_ejecucion
        if (str_contains($compact, 'tiempodeejecucion') || str_contains($compact, 'tiempoejecucion')) {
            return 'tiempo_ejecucion';
        }

        return null;
    }

    /** Convierte un valor crudo al tipo correcto según el campo */
    private function castValue(string $field, mixed $raw): mixed
    {
        if ($raw === null || $raw === '') return null;

        // ── Horas/duraciones: fracción de día o texto → H:i:s ────────────────────
        // meta_td y tiempo_ejecucion vienen como 0.00277... (fracción de 1 día) o "08:20:00 a. m."
        if (in_array($field, self::TIME_FIELDS, true)) {
            if (is_numeric($raw)) {
                try {
                    $dt = ExcelDate::excelToDateTimeObject((float) $raw);
                    return $dt->format('H:i:s');
                } catch (\Throwable) {
                    return null;
                }
            }
            $str = trim((string) $raw);
            $isPm = (bool) preg_match('/p\.\s*m\.|pm/i', $str);
            $isAm = (bool) preg_match('/a\.\s*m\.|am/i', $str);
            $strClean = trim(preg_replace('/\s*(a\.\s*m\.|p\.\s*m\.|am|pm)/i', '', $str));

            if (preg_match('/^(\d{1,3}):(\d{2})(?::(\d{2}))?$/', $strClean, $m)) {
                $h = (int) $m[1];
                $i = (int) $m[2];
                $s = isset($m[3]) ? (int) $m[3] : 0;
                if ($isPm && $h < 12) $h += 12;
                if ($isAm && $h === 12) $h = 0;
                return sprintf('%02d:%02d:%02d', $h, $i, $s);
            }
            return null;
        }

        // ── Fechas ────────────────────────────────────────────────────────
        if (in_array($field, self::DATE_FIELDS, true)) {
            if (is_numeric($raw)) {
                try {
                    return ExcelDate::excelToDateTimeObject($raw)->format('Y-m-d H:i:s');
                } catch (\Throwable) {}
            }
            $str = trim((string) $raw);
            $strNorm = preg_replace('/\s*a\.\s*m\.\s*/i', ' AM ', $str);
            $strNorm = preg_replace('/\s*p\.\s*m\.\s*/i', ' PM ', $strNorm);
            $strNorm = preg_replace('/\s*a\.\s*m\s*/i', ' AM ', $strNorm);
            $strNorm = preg_replace('/\s*p\.\s*m\s*/i', ' PM ', $strNorm);
            $strNorm = preg_replace('/\s+/', ' ', trim($strNorm));

            try {
                if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $strNorm, $pm)) {
                    $strNorm = "{$pm[1]}-{$pm[2]}-{$pm[3]}" . (strlen($strNorm) > strlen($pm[0]) ? substr($strNorm, strlen($pm[0])) : '');
                }
                return Carbon::parse($strNorm)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }

        // ── Porcentajes: Excel guarda 100% como 1.0 → multiplicar ×100 ───
        if (in_array($field, self::PCT_FIELDS, true)) {
            if (!is_numeric($raw)) return null;
            $val = (float) $raw;
            // Si ya viene escalado (ej: 85) lo dejamos tal cual.
            // Si viene como fracción ≤ 1 (ej: 0.85, 1.0) → ×100.
            return $val <= 1.0 ? round($val * 100, 2) : round($val, 2);
        }

        // ── Mes: puede venir como número (8) o texto ("agosto") ──────────
        if ($field === 'mes') {
            if (is_numeric($raw)) return (int) $raw;
            $key = mb_strtolower(trim((string) $raw));
            $key = str_replace(['á','é','í','ó','ú'], ['a','e','i','o','u'], $key);
            return self::MESES_MAP[$key] ?? null;
        }

        // ── Enteros simples ───────────────────────────────────────────────
        if (in_array($field, self::INT_FIELDS, true)) {
            return is_numeric($raw) ? (int) $raw : null;
        }

        // ── Numérico como string limpio (odómetro, id_form) ──────────────
        if (in_array($field, self::NUMERIC_STRING_FIELDS, true)) {
            if (is_numeric($raw)) {
                $float = (float) $raw;
                return ($float == (int) $float)
                    ? (string) (int) $float
                    : (string) $float;
            }
            return trim((string) $raw);
        }

        return trim((string) $raw);
    }
}
