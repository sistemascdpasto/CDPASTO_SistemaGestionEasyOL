<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\ImportarAcisRequest;
use App\Services\Seguridad\AciImportService;
use Illuminate\Http\RedirectResponse;

class AciImportController extends Controller
{
    public function store(ImportarAcisRequest $request, AciImportService $service): RedirectResponse
    {
        $rutas = collect($request->file('archivos'))->map(fn ($archivo) => $archivo->getRealPath())->all();

        $resultado = $service->importar($rutas);

        $mensaje = "Importación completa ({$resultado['archivos_procesados']} archivo(s)): {$resultado['creados']} creados, "
            ."{$resultado['actualizados']} actualizados, "
            ."{$resultado['omitidos_sin_colaborador']} omitidos por QR sin colaborador, "
            ."{$resultado['errores']} con error.";

        $tipo = match (true) {
            $resultado['creados'] === 0 && $resultado['actualizados'] === 0 => 'error',
            $resultado['omitidos_sin_colaborador'] > 0 || $resultado['errores'] > 0 => 'warning',
            default => 'success',
        };

        return to_route('seguridad.acis.index')->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }
}
