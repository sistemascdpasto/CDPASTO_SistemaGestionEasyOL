<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\StoreColaboradorEntrenamientoRequest;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\Entrenamiento;
use Illuminate\Http\RedirectResponse;

class ColaboradorEntrenamientoController extends Controller
{
    /**
     * HU (Paso 6): si se elige un entrenamiento existente se reutiliza tal
     * cual; si se escribe un nombre nuevo, se crea el catálogo bajo demanda.
     * El historial nunca se sobrescribe: cada envío crea una fila nueva, aun
     * repitiendo el mismo entrenamiento en otra fecha.
     */
    public function store(StoreColaboradorEntrenamientoRequest $request, Colaborador $colaborador): RedirectResponse
    {
        $entrenamientoId = $request->validated('entrenamiento_id');

        if (! $entrenamientoId) {
            $entrenamientoId = Entrenamiento::firstOrCreate(['nombre' => $request->validated('entrenamiento_nombre')])->id;
        }

        $colaborador->entrenamientos()->create([
            'entrenamiento_id' => $entrenamientoId,
            'fecha_registro' => $request->validated('fecha_registro'),
            'hora_registro' => $request->validated('hora_registro'),
            'registrado_por_id' => $request->user()->id,
        ]);

        return back()->with('status', 'Entrenamiento registrado correctamente.');
    }
}
