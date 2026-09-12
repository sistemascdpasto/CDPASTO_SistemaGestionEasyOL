<?php

namespace App\Services\Seguridad;

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
        'MES'         => 'mes',
        'CEDULA'      => 'cedula',
        'NOMBRE'      => 'nombre',
        'CARGO'       => 'cargo',
        'INDICADOR'   => 'indicador_1',
        'PILAR'       => 'pilar_1',
        'TOTAL'       => 'total_1',
        'META'        => 'meta_1',
        'INDICADOR2'  => 'indicador_2',
        'PILAR2'      => 'pilar_2',
        'TOTAL2'      => 'total_2',
        'META2'       => 'meta_2',
        'INDICADOR3'  => 'indicador_3',
        'PILAR3'      => 'pilar_3',
        'TOTAL3'      => 'total_3',
        'META3'       => 'meta_3',
        'PODIUM'      => 'podium',
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
                $hoja = $spreadsheet->getActiveSheet();
            } catch (Throwable $e) {
                Log::warning("Importación de Incentivos: no se pudo cargar el archivo {$rutaArchivo}: {$e->getMessage()}");
                $resultado['errores']++;
                continue;
            }

            $resultado['archivos_procesados']++;

            // Convertir a array con coordenadas de columna (A, B, C…)
            $filas = $hoja->toArray(null, true, true, true);

            // Primera fila = encabezados
            $encabezados = array_shift($filas) ?? [];
            $mapaColumnas = $this->resolverMapaColumnas($encabezados);

            foreach ($filas as $numeroFila => $fila) {
                $this->procesarFila($fila, $mapaColumnas, $numeroFila, $resultado);
            }
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, array{campo: ?string, encabezado: string}>  $mapaColumnas
     * @param  array{creados: int, actualizados: int, omitidos_sin_colaborador: int, errores: int, archivos_procesados: int}  $resultado
     */
    private function procesarFila(array $fila, array $mapaColumnas, int|string $numeroFila, array &$resultado): void
    {
        $valores = $this->extraerValoresPorCampo($fila, $mapaColumnas);

        // Ignorar filas completamente vacías
        $cedula = trim((string) ($valores['cedula'] ?? ''));
        if ($cedula === '') {
            return;
        }

        try {
            // Buscar colaborador por cédula
            $colaborador = Colaborador::where('cedula', $cedula)->first();

            if (! $colaborador) {
                $resultado['omitidos_sin_colaborador']++;
                return;
            }

            $mes = trim((string) ($valores['mes'] ?? ''));

            // Upsert: si ya existe el registro para este colaborador y mes, se actualiza
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

    /**
     * Construye el mapa letra-de-columna → {campo, encabezado}.
     *
     * Para encabezados repetidos (indicador, pilar, total, meta) se resuelven
     * en orden de aparición: la primera ocurrencia se mapea a _1, la segunda
     * a _2 y la tercera a _3.
     *
     * @param  array<string, mixed>  $encabezados  [letra => texto]
     * @return array<string, array{campo: ?string, encabezado: string}>
     */
    private function resolverMapaColumnas(array $encabezados): array
    {
        $mapa = [];

        // Contadores para encabezados repetidos
        $contadores = [
            'INDICADOR' => 0,
            'PILAR'     => 0,
            'TOTAL'     => 0,
            'META'      => 0,
        ];

        $sufijos = [1 => '', 2 => '2', 3 => '3'];

        foreach ($encabezados as $letra => $texto) {
            $textoLimpio = trim((string) $texto);
            if ($textoLimpio === '') {
                continue;
            }

            // Normalizar: quitar tildes, espacios y poner en mayúsculas
            $normalizado = $this->normalizar($textoLimpio);

            // Detectar si es un encabezado repetible
            $campo = null;
            $esRepetible = false;

            foreach (array_keys($contadores) as $clave) {
                if ($normalizado === $clave) {
                    $esRepetible = true;
                    $contadores[$clave]++;
                    $n = $contadores[$clave];

                    if ($n <= 3) {
                        $sufijo = $sufijos[$n];
                        $campo = self::HEADER_MAP[$clave.$sufijo] ?? null;
                    }
                    break;
                }
            }

            if (! $esRepetible) {
                $campo = self::HEADER_MAP[$normalizado] ?? null;
            }

            $mapa[$letra] = [
                'campo'     => $campo,
                'encabezado' => $textoLimpio,
            ];
        }

        return $mapa;
    }

    /**
     * @param  array<string, mixed>  $fila
     * @param  array<string, array{campo: ?string, encabezado: string}>  $mapaColumnas
     * @return array<string, mixed>
     */
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

    /**
     * @param  array<string, mixed>  $valores
     * @return array<string, mixed>
     */
    private function mapearFila(array $valores, int $colaboradorId): array
    {
        $camposDecimales = ['total_1', 'meta_1', 'total_2', 'meta_2', 'total_3', 'meta_3'];

        $datos = ['colaborador_id' => $colaboradorId];

        foreach ($valores as $campo => $valor) {
            if (in_array($campo, $camposDecimales, true)) {
                // Normalizar separador decimal (coma → punto) y convertir a número
                $valorNormalizado = str_replace(',', '.', (string) $valor);
                $datos[$campo] = is_numeric($valorNormalizado) ? (float) $valorNormalizado : null;
            } else {
                $datos[$campo] = $valor;
            }
        }

        return $datos;
    }
}
