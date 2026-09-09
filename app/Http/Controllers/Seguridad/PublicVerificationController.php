<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\PruebaAlcoholemia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PublicVerificationController extends Controller
{
    /**
     * HU037: verificación pública (sin autenticación) de la autenticidad de un
     * registro de prueba a partir del QR. Expone solo datos mínimos.
     */
    public function show(Request $request, PruebaAlcoholemia $prueba, string $token): Response
    {
        abort_unless($prueba->qr_token && hash_equals($prueba->qr_token, $token), 404);

        $prueba->load('colaborador:id,nombres,apellidos');

        return Inertia::render('seguridad/verificacion', [
            'registro' => [
                'colaborador' => $prueba->colaborador?->nombre_completo,
                'tipo' => $prueba->tipo,
                'fecha' => $prueba->fecha_hora->format('d/m/Y H:i'),
                'evaluacion' => $prueba->estado === 'programada' ? null : $prueba->evaluacion(),
                'estado' => $prueba->estado,
            ],
        ]);
    }
}
