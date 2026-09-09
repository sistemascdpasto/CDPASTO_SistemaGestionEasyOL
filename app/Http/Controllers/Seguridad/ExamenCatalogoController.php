<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\Examen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExamenCatalogoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('seguridad/examenes-medicos/catalogo', [
            'examenes' => Examen::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'tipo_examen' => ['nullable', 'string', 'max:100'],
        ]);

        Examen::create($data);

        return back()->with('status', 'Examen creado correctamente.');
    }

    public function update(Request $request, Examen $examen): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'tipo_examen' => ['nullable', 'string', 'max:100'],
            'activo' => ['required', 'boolean'],
        ]);

        $examen->update($data);

        return back()->with('status', 'Examen actualizado correctamente.');
    }
}
