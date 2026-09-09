<?php

namespace App\Services\Seguridad;

use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\EvaluacionRecomendacion;
use App\Models\Seguridad\Recomendacion;
use App\Services\Seguridad\Concerns\NormalizaCatalogos;

/**
 * HU-033 CA-033.2: compara el texto extraído del PDF del examen contra el
 * catálogo maestro de recomendaciones y marca automáticamente las que
 * coincidan (mismo `normalizar()` que ya usan los importadores de
 * ACI/Colaboradores/OWD). Nunca borra ni desactiva una recomendación
 * existente — el catálogo es fijo, y una coincidencia que ya estaba
 * marcada (a mano o de un PDF anterior) no se duplica.
 */
class RecomendacionPdfMatcherService
{
    use NormalizaCatalogos;

    public function marcarCoincidencias(EvaluacionMedica $evaluacion, string $textoPdf): int
    {
        $textoNormalizado = $this->normalizar($textoPdf);

        if ($textoNormalizado === '') {
            return 0;
        }

        $marcadas = 0;

        foreach (Recomendacion::where('activo', true)->get() as $recomendacion) {
            $nombreNormalizado = $this->normalizar($recomendacion->nombre);

            if ($nombreNormalizado === '' || ! str_contains($textoNormalizado, $nombreNormalizado)) {
                continue;
            }

            $existente = EvaluacionRecomendacion::where('evaluacion_medica_id', $evaluacion->id)
                ->where('recomendacion_id', $recomendacion->id)
                ->exists();

            if ($existente) {
                continue;
            }

            EvaluacionRecomendacion::create([
                'evaluacion_medica_id' => $evaluacion->id,
                'recomendacion_id' => $recomendacion->id,
                'activa' => true,
                'origen' => 'PDF',
                'fecha_registro' => now()->toDateString(),
            ]);

            $marcadas++;
        }

        return $marcadas;
    }
}
