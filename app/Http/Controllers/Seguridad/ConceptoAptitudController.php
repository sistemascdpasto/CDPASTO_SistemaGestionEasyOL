<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\ConceptoAptitud;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConceptoAptitudController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('seguridad/examenes-medicos/conceptos-aptitud', [
            'conceptos' => ConceptoAptitud::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
        ]);

        ConceptoAptitud::create($data);

        return back()->with('status', 'Concepto de aptitud creado correctamente.');
    }

    public function update(Request $request, ConceptoAptitud $conceptoAptitud): RedirectResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'activo' => ['required', 'boolean'],
        ]);

        $conceptoAptitud->update($data);

        return back()->with('status', 'Concepto de aptitud actualizado correctamente.');
    }
}
