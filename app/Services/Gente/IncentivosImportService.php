<?php

namespace App\Services\Gente;

use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\Incentivo;
use App\Services\Seguridad\Concerns\NormalizaCatalogos;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

/**
 * Importa registros de incentivos desde un archivo Excel.
 *
 * Las columnas se resuelven dinámicamente por texto de encabezado (normalizado)
 * para tolerar que cambien de orden entre versiones del archivo.
 *
 * Solo se guardan las filas cuya cédula exista en la tabla de colaboradores.
 * Si el registro ya existe (mismo colaborador_id + mes) se actualiza; si no,
 * se crea.
 */
class IncentivosImportService
{
    use NormalizaCatalogos;

    /**
     * Encabezado normalizado (sin tildes, sin espacios, mayúsculas) => campo de `incentivos`.
     */
    private const HEADER_MAP = [
        'MES'        => 'mes',
        'CEDULA'     => 'cedula',
        'NOMBRE'     => 'nombre',
        'CARGO'      => 'cargo',
        'INDICADOR'  => 'indicador_1',
        'PILAR'      => 'pilar_1',
        'TOTAL'      => 'total_1',
        'META'       => 'meta_1',
        'INDICADOR2' => 'indicador_2',
        'PILAR2'     => 'pilar_2',
        'TOTAL2'     => 'total_2',
        'META2'      => 'meta_2',
        'INDICADOR3' => 'indicador_3',
        'PILAR3'     => 'pilar_3',
        'TOTAL3'     => 'total_3',
        'META3'      => 'meta_3',
        'PODIUM'           => 'podium',
        'VALORINDICADOR1'  => 'valor_indicador_1',
        'VALORINDICADOR2'  => 'valor_indicador_2',
        'VALORINDICADOR3'  => 'valor_indicador_3',
        'TOTAL4'           => 'total_4',
        'META4'            => 'meta_4',
    ];

    /**
     * @param  array<int, string>  $rutasArchivos
     * @return array{creados: int, actualizados: int, omitidos_sin_colaborador: int, errores: int, archivos_procesados: int}
     */
    public function importar(array $rutasArchivos): array
    {
        $resultado = [
            'creados'                  => 0,
            'actualizados'             => 0,
            'omitidos_sin_colaborador' => 0,
            'errores'                  => 0,
            'archivos_procesados'      => 0,
        ];

        foreach ($rutasArchivos as $rutaArchivo) {
            try {
                $spreadsheet = IOFactory::load($rutaArchivo);
                $hoja        = $spreadsheet->getActiveSheet();
            } catch (Throwable $e) {
                Log::warning("Importación de Incentivos: no se pudo cargar el archivo {$rutaArchivo}: {$e->getMessage()}");
                $resultado['errores']++;
                continue;
            }

            $resultado['archivos_procesados']++;

            $filas = $hoja->toArray(null, true, true, true);

            // Buscar la fila que contiene los encabezados reales (la que tenga 'Cedula' o 'Mes')
            // porque el Excel puede tener filas de título antes de los encabezados.
            $encabezados  = [];
            $filasDatos   = [];
            $encontrado   = false;

            foreach ($filas as $idx => $fila) {
                if (! $encontrado) {
                    foreach ($fila as $celda) {
                        $norm = $this->normalizar((string) ($celda ?? ''));
                        if ($norm === 'CEDULA' || $norm === 'MES') {
                            $encabezados = $fila;
                            $encontrado  = true;
                            break;
                        }
                    }
                } else {
                    $filasDatos[$idx] = $fila;
                }
            }

            if (! $encontrado) {
                Log::warning("Importación de Incentivos: no se encontró fila de encabezados con 'Mes' o 'Cedula'.");
                $resultado['errores']++;
                continue;
            }

            Log::info('Incentivos encabezados encontrados: ' . json_encode(array_values($encabezados)));
            $mapaColumnas = $this->resolverMapaColumnas($encabezados);

            foreach ($filasDatos as $numeroFila => $fila) {
                $this->procesarFila($fila, $mapaColumnas, $numeroFila, $resultado);
            }
        }

        return $resultado;
    }

    private function procesarFila(array $fila, array $mapaColumnas, int|string $numeroFila, array &$resultado): void
    {
        $valores = $this->extraerValoresPorCampo($fila, $mapaColumnas);

        // La cédula puede venir como número flotante desde Excel (ej: 1233191710.0)
        // Se convierte a entero string para que coincida con la BD.
        $cedulaCruda = $valores['cedula'] ?? '';
        if (is_numeric($cedulaCruda)) {
            $cedulaCruda = (string) (int) $cedulaCruda;
        }
        $cedula = trim((string) $cedulaCruda);

        Log::info("Incentivos fila {$numeroFila}: cedula=[{$cedula}] mapa=" . json_encode(array_keys(array_filter($valores, fn($v) => $v !== null))));

        if ($cedula === '') {
            return;
        }

        try {
            $colaborador = Colaborador::where('cedula', $cedula)->first();

            if (! $colaborador) {
                $resultado['omitidos_sin_colaborador']++;
                return;
            }

            $mes      = trim((string) ($valores['mes'] ?? ''));
            $existente = Incentivo::where('colaborador_id', $colaborador->id)
                ->where('mes', $mes)
                ->first();

            $datos = $this->mapearFila($valores, $colaborador->id);

            if ($existente) {
                $existente->update($datos);
                $resultado['actualizados']++;
            } else {
                Incentivo::create($datos);
                $resultado['creados']++;
            }
        } catch (Throwable $e) {
            Log::warning("Importación de Incentivos: error en fila {$numeroFila} (cédula {$cedula}): {$e->getMessage()}");
            $resultado['errores']++;
        }
    }

    private function resolverMapaColumnas(array $encabezados): array
    {
        $mapa      = [];
        $contadores = ['INDICADOR' => 0, 'PILAR' => 0, 'TOTAL' => 0, 'META' => 0];
        $sufijos    = [1 => '', 2 => '2', 3 => '3'];

        foreach ($encabezados as $letra => $texto) {
            $textoLimpio = trim((string) $texto);
            if ($textoLimpio === '') {
                continue;
            }

            $normalizado = $this->normalizar($textoLimpio);
            $campo       = null;
            $esRepetible = false;

            foreach (array_keys($contadores) as $clave) {
                if ($normalizado === $clave) {
                    $esRepetible = true;
                    $contadores[$clave]++;
                    $n = $contadores[$clave];
                    if ($n <= 3) {
                        $campo = self::HEADER_MAP[$clave.$sufijos[$n]] ?? null;
                    }
                    break;
                }
            }

            if (! $esRepetible) {
                $campo = self::HEADER_MAP[$normalizado] ?? null;
            }

            $mapa[$letra] = ['campo' => $campo, 'encabezado' => $textoLimpio];
        }

        return $mapa;
    }

    private function extraerValoresPorCampo(array $fila, array $mapaColumnas): array
    {
        $valores = [];
        foreach ($mapaColumnas as $letra => $info) {
            $valorCrudo = trim((string) ($fila[$letra] ?? ''));
            if ($info['campo'] !== null) {
                $valores[$info['campo']] = $valorCrudo !== '' ? $valorCrudo : null;
            }
        }
        return $valores;
    }

    private function mapearFila(array $valores, int $colaboradorId): array
    {
        $camposDecimales = ['total_1', 'meta_1', 'total_2', 'meta_2', 'total_3', 'meta_3',
                            'valor_indicador_1', 'valor_indicador_2', 'valor_indicador_3',
                            'total_4', 'meta_4'];
        $datos           = ['colaborador_id' => $colaboradorId];

        foreach ($valores as $campo => $valor) {
            if (in_array($campo, $camposDecimales, true)) {
                $v             = str_replace(',', '.', (string) $valor);
                $datos[$campo] = is_numeric($v) ? (float) $v : null;
            } else {
                $datos[$campo] = $valor;
            }
        }

        return $datos;
    }
}
