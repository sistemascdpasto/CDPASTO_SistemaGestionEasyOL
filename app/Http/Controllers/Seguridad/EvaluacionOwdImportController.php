<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\ImportarEvaluacionesOwdRequest;
use App\Services\Seguridad\EvaluacionOwdImportService;
use Illuminate\Http\RedirectResponse;

class EvaluacionOwdImportController extends Controller
{
    public function store(ImportarEvaluacionesOwdRequest $request, EvaluacionOwdImportService $service): RedirectResponse
    {
        $rutas = collect($request->file('archivos'))
            ->mapWithKeys(fn ($archivo) => [$archivo->getRealPath() => $archivo->getClientOriginalName()])
            ->all();

        $resultado = $service->importar($rutas, $request->user());

        $mensaje = "Importación completa ({$resultado['archivos_procesados']} archivo(s), {$resultado['evaluaciones_identificadas']} evaluaciones): "
            ."{$resultado['nuevos']} preguntas nuevas, "
            ."{$resultado['duplicados']} duplicadas, "
            ."{$resultado['sin_coincidencia_qr']} sin coincidencia de QR, "
            ."{$resultado['errores']} con error.";

        $tipo = match (true) {
            $resultado['nuevos'] === 0 && $resultado['archivos_procesados'] === 0 => 'error',
            $resultado['sin_coincidencia_qr'] > 0 || $resultado['errores'] > 0 => 'warning',
            default => 'success',
        };

        return to_route('seguridad.evaluaciones-owd.index')->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }
}
