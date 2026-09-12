<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\EvaluacionMedica;
use App\Models\Seguridad\EvaluacionRecomendacion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EvaluacionRecomendacionController extends Controller
{
    public function store(Request $request, EvaluacionMedica $evaluacion): RedirectResponse
    {
        $data = $request->validate([
            'recomendacion_id' => [
                'required', 'integer', 'exists:recomendaciones,id',
                Rule::unique('evaluacion_recomendaciones', 'recomendacion_id')->where('evaluacion_medica_id', $evaluacion->id),
            ],
            'observacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $soportePaths = [];
        if ($request->hasFile('soporte')) {
            $files = $request->file('soporte');
            $fileArray = is_array($files) ? $files : [$files];
            foreach ($fileArray as $file) {
                if ($file && $file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $originalName);
                    $soportePaths[] = $file->storeAs('recomendaciones', $safeName, 'public');
                }
            }
        }

        $soportePath = match(count($soportePaths)) {
            0 => null,
            1 => $soportePaths[0],
            default => json_encode($soportePaths),
        };

        $evaluacionRecomendacion = $evaluacion->recomendaciones()->create([
            'recomendacion_id' => $data['recomendacion_id'],
            'observacion'      => $data['observacion'] ?? null,
            'soporte_path'     => $soportePath,
            'activa'           => true,
            'origen'           => 'manual',
            'fecha_registro'   => now()->toDateString(),
        ]);

        // Crear un seguimiento inicial automático cuando se crea la recomendación
        // La observación del seguimiento inicial hereda la observación de la recomendación
        $evaluacionRecomendacion->seguimientos()->create([
            'fecha_seguimiento' => now()->toDateString(),
            'estado_seguimiento' => 'Pendiente',
            'observacion' => $data['observacion'] ?? null, // Heredar la observación de la recomendación
            'responsable_id' => $request->user()?->id,
            'fecha_proximo_seguimiento' => null,
            'carta_recomendacion_entregada' => false,
            'soporte_path' => $soportePath,
        ]);

        return back()->with('status', 'Recomendación agregada.');
    }

    /**
     * HU-054 regla 3: el seguimiento no elimina la recomendación — solo se
     * marca como no vigente para conservar el histórico.
     */
    public function toggleActiva(EvaluacionMedica $evaluacion, EvaluacionRecomendacion $evaluacionRecomendacion): RedirectResponse
    {
        $evaluacionRecomendacion->update(['activa' => ! $evaluacionRecomendacion->activa]);

        return back()->with('status', $evaluacionRecomendacion->activa ? 'Recomendación marcada como vigente.' : 'Recomendación marcada como no vigente.');
    }

    public function destroy(EvaluacionMedica $evaluacion, EvaluacionRecomendacion $evaluacionRecomendacion): RedirectResponse
    {
        $evaluacionRecomendacion->seguimientos()->delete();
        $evaluacionRecomendacion->delete();

        return back()->with('status', 'Recomendación eliminada.');
    }
}
