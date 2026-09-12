<?php

namespace App\Services\Seguridad;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Consume la API pública (Socrata/SODA) del portal Datos Abiertos Colombia
 * (datos.gov.co) para obtener información oficial de vías afectadas y tramos
 * críticos de siniestralidad, filtrada al departamento de Nariño.
 *
 * A diferencia de Waze (que no expone una API pública y bloquea el scraping),
 * estos son datasets oficiales, documentados y sin autenticación.
 */
class RutasCriticasDatosAbiertosService
{
    private const BASE_URL = 'https://www.datos.gov.co/resource';

    /** "Estado de Vías" — Dirección de Tránsito y Transporte, Policía Nacional (DIPON). */
    private const DATASET_ESTADO_VIAS = '7i66-rps2';

    /** "Sectores Críticos de Siniestralidad Vial" — ANSV / ANI / INVÍAS. */
    private const DATASET_SECTORES_CRITICOS = 'rs3u-8r4q';

    private const CACHE_TTL_SEGUNDOS = 6 * 60 * 60;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerAfectacionesVia(): array
    {
        return $this->consultarConCache('seguridad.rutas-criticas.afectaciones-vias.v2', self::DATASET_ESTADO_VIAS, [
                'departamento' => 'Nariño',
                '$order' => 'fecha DESC',
                '$limit' => 30,
            ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerSectoresCriticos(): array
    {
        return $this->consultarConCache('seguridad.rutas-criticas.sectores-criticos.v2', self::DATASET_SECTORES_CRITICOS, [
                'departamento' => 'NARIÑO',
                '$order' => 'fallecidos DESC',
                '$limit' => 30,
            ]);
    }

    /**
     * Conserva solamente respuestas utiles. Cachear un error de red como []
     * deja la pantalla sin datos durante todo el TTL.
     *
     * @param  array<string, string|int>  $parametros
     * @return array<int, array<string, mixed>>
     */
    private function consultarConCache(string $clave, string $dataset, array $parametros): array
    {
        $datosEnCache = Cache::get($clave);

        if (is_array($datosEnCache) && $datosEnCache !== []) {
            return $datosEnCache;
        }

        $datos = $this->consultar($dataset, $parametros);

        if ($datos !== []) {
            Cache::put($clave, $datos, self::CACHE_TTL_SEGUNDOS);
        }

        return $datos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function consultar(string $dataset, array $parametros): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders(['User-Agent' => 'SistemaGestionEasyLogistica-SGSST/1.0'])
                ->timeout(10)
                ->retry([500, 1000, 2000])
                ->get(self::BASE_URL."/{$dataset}.json", $parametros);

            if (! $response->successful()) {
                Log::warning("Datos Abiertos Colombia devolvio un error para el dataset {$dataset}", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            return $response->json() ?? [];
        } catch (Throwable $e) {
            Log::warning("No se pudo consultar el dataset {$dataset} de Datos Abiertos Colombia", ['error' => $e->getMessage()]);

            return [];
        }
    }
}