<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Services\Seguridad\RutasCriticasDatosAbiertosService;
use Inertia\Inertia;
use Inertia\Response;

class RutaCriticaController extends Controller
{
    public function index(RutasCriticasDatosAbiertosService $servicio): Response
    {
        return Inertia::render('seguridad/rutas-criticas/index', [
            'afectacionesVia' => $servicio->obtenerAfectacionesVia(),
            'sectoresCriticos' => $servicio->obtenerSectoresCriticos(),
        ]);
    }
}