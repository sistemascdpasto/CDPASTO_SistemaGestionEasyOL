<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\ImportarColaboradoresRequest;
use App\Services\Seguridad\ColaboradorImportService;
use Illuminate\Http\RedirectResponse;

class ColaboradorImportController extends Controller
{
    public function store(ImportarColaboradoresRequest $request, ColaboradorImportService $service): RedirectResponse
    {
        $resultado = $service->importar($request->file('archivo')->getRealPath());

        $mensaje = "Importación completa: {$resultado['creados']} creados, "
            ."{$resultado['omitidos']} omitidos (cédula ya existente), "
            ."{$resultado['errores']} con error.";

        $tipo = match (true) {
            $resultado['creados'] === 0 => 'error',
            $resultado['omitidos'] > 0 || $resultado['errores'] > 0 => 'warning',
            default => 'success',
        };

        return to_route('gente.colaboradores.index')->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }
}
