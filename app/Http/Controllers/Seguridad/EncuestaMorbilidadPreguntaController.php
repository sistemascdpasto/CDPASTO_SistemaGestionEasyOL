<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\EncuestaMorbilidadPregunta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EncuestaMorbilidadPreguntaController extends Controller
{
    public function index(): Response
    {
        $preguntas = EncuestaMorbilidadPregunta::orderBy('seccion_numero')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        return Inertia::render('seguridad/encuestas-morbilidad/preguntas', [
            'preguntas' => $preguntas,
            'tiposValidos' => [
                ['value' => 'si_no',             'label' => 'Sí / No'],
                ['value' => 'si_no_detalle',     'label' => 'Sí / No (con especificación de detalle si marca Sí)'],
                ['value' => 'aplica_detalle',    'label' => 'Aplica / No aplica (con detalle si marca Aplica)'],
                ['value' => 'texto_libre',       'label' => 'Texto libre (comentario/observaciones)'],
                ['value' => 'numero',            'label' => 'Número (ej. Peso o Talla)'],
                ['value' => 'checkbox_multiple', 'label' => 'Múltiple selección (checkboxes)'],
                ['value' => 'segmento_corporal', 'label' => 'Segmento corporal (Frecuencia y Severidad)'],
                ['value' => 'mano_dominante',    'label' => 'Mano dominante (Derecha / Izquierda)'],
                ['value' => 'actividades_salud', 'label' => 'Actividades de salud (checkboxes)'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'seccion_numero' => ['required', 'integer', 'min:1', 'max:50'],
            'seccion_titulo' => ['required', 'string', 'max:150'],
            'texto'          => ['required', 'string', 'max:500'],
            'tipo'           => ['required', 'string', 'in:si_no,si_no_detalle,aplica_detalle,texto_libre,numero,checkbox_multiple,actividades_salud,mano_dominante,segmento_corporal'],
            'obligatorio'    => ['required', 'boolean'],
            'opciones'       => ['nullable', 'array'],
            'con_otro'       => ['nullable', 'boolean'],
            'segmento'       => ['nullable', 'string', 'max:100'],
        ]);

        $maxNumero = EncuestaMorbilidadPregunta::max('numero_pregunta') ?? 0;
        $data['numero_pregunta'] = $maxNumero + 1;
        $data['con_otro'] = $data['con_otro'] ?? false;
        $data['activo'] = true;

        EncuestaMorbilidadPregunta::create($data);

        return back()->with('status', 'Pregunta agregada correctamente al catálogo.');
    }

    public function update(Request $request, EncuestaMorbilidadPregunta $pregunta): RedirectResponse
    {
        $data = $request->validate([
            'seccion_numero' => ['required', 'integer', 'min:1', 'max:50'],
            'seccion_titulo' => ['required', 'string', 'max:150'],
            'texto'          => ['required', 'string', 'max:500'],
            'tipo'           => ['required', 'string', 'in:si_no,si_no_detalle,aplica_detalle,texto_libre,numero,checkbox_multiple,actividades_salud,mano_dominante,segmento_corporal'],
            'obligatorio'    => ['required', 'boolean'],
            'opciones'       => ['nullable', 'array'],
            'con_otro'       => ['nullable', 'boolean'],
            'segmento'       => ['nullable', 'string', 'max:100'],
            'activo'         => ['required', 'boolean'],
        ]);

        $pregunta->update($data);

        return back()->with('status', 'Pregunta actualizada correctamente.');
    }

    public function destroy(EncuestaMorbilidadPregunta $pregunta): RedirectResponse
    {
        $pregunta->delete();

        return back()->with('status', 'Pregunta eliminada del catálogo.');
    }
}
