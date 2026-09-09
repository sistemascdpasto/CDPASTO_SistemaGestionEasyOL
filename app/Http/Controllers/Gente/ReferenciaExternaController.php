<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Services\Seguridad\InstitucionesSenaService;
use App\Services\Seguridad\UbicacionesColombiaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoints JSON de solo lectura que respaldan los selects externos del
 * wizard de colaboradores (HU02, Paso 1). Mantienen las URLs/llamadas a
 * terceros en el servidor en vez de exponerlas al navegador.
 */
class ReferenciaExternaController extends Controller
{
    public function departamentos(UbicacionesColombiaService $service): JsonResponse
    {
        return response()->json(['data' => $service->departamentos()]);
    }

    public function ciudades(Request $request, UbicacionesColombiaService $service): JsonResponse
    {
        $departamentoId = $request->integer('departamento_id');

        if (! $departamentoId) {
            return response()->json(['data' => []]);
        }

        return response()->json(['data' => $service->ciudades($departamentoId)]);
    }

    public function institucionesSena(InstitucionesSenaService $service): JsonResponse
    {
        return response()->json(['data' => $service->instituciones()]);
    }
}
