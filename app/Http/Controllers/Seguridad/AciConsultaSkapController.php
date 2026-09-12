<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Aci;
use App\Models\Seguridad\Colaborador;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AciConsultaSkapController extends Controller
{
    public function index(Request $request): Response
    {
        $colaboradores = Colaborador::query()
            ->completos()
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellidos', 'cedula', 'codigo_qr_skap', 'centro', 'cargo', 'turno', 'is_active']);

        $colaboradorId = $request->integer('colaborador_id') ?: null;
        $colaboradorSeleccionado = null;
        $historial = null;

        if ($colaboradorId) {
            $colaborador = Colaborador::find($colaboradorId);

            if ($colaborador) {
                $colaboradorSeleccionado = [
                    ...$colaborador->only(['id', 'nombres', 'apellidos', 'cedula', 'codigo_qr_skap', 'centro', 'area', 'cargo', 'is_active']),
                    'estado_skap' => $colaborador->estado_skap,
                ];

                $historial = Aci::where('colaborador_id', $colaborador->id)
                    ->latest('fecha_incidente')
                    ->paginate(10)
                    ->withQueryString();
            }
        }

        return Inertia::render('seguridad/acis/consultar-qr', [
            'colaboradores' => $colaboradores,
            'colaboradorSeleccionado' => $colaboradorSeleccionado,
            'historial' => $historial,
        ]);
    }
}
