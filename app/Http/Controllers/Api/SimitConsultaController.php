<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSimitConsultaRequest;
use App\Models\SimitConsulta;
use Illuminate\Http\JsonResponse;

class SimitConsultaController extends Controller
{
    /**
     * Recibe una consulta del monitor SIMIT que corre en la PC local
     * (ver simit_monitor.py) y la guarda tal cual quedo en su base de
     * datos local.
     */
    public function store(StoreSimitConsultaRequest $request): JsonResponse
    {
        $data = $request->validated();

        $consulta = SimitConsulta::create([
            'placa' => $data['placa'],
            'fecha_hora' => $data['timestamp'],
            'status' => $data['status'],
            'raw_text' => $data['raw_text'] ?? null,
            'screenshot_nombre' => $request->file('screenshot')?->getClientOriginalName(),
            'screenshot' => $request->file('screenshot')?->get(),
        ]);

        return response()->json(['id' => $consulta->id], 201);
    }
}
