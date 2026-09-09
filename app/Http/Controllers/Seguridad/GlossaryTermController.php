<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Requests\Seguridad\StoreGlossaryTermRequest;
use App\Models\Seguridad\GlossaryTerm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlossaryTermController
{
    public const CATEGORIES = [
        'SEÑALIZACIÓN DE LA VÍA',
        'CONDICIONES DEL PAVIMENTO',
        'VISIBILIDAD Y CLIMA',
        'COMPORTAMIENTO DEL CONDUCTOR',
        'ESTADO DEL VEHÍCULO',
        'SEÑALES DE TRÁNSITO',
        'GLOSARIO TÉCNICO INVÍAS',
    ];

    public function index(Request $request): Response
    {
        $query = GlossaryTerm::query();

        if ($search = $request->input('search')) {
            $query->search($search);
        }

        if ($categoria = $request->input('categoria')) {
            $query->byCategory($categoria);
        }

        match ($request->input('orden')) {
            'nombre_asc' => $query->orderBy('nombre'),
            'nombre_desc' => $query->orderByDesc('nombre'),
            default => $query->orderBy('categoria')->orderBy('nombre'),
        };

        $terms = $query->paginate(15)->withQueryString();

        return Inertia::render('seguridad/glosario/index', [
            'terms' => $terms,
            'filters' => $request->only('search', 'categoria', 'orden'),
            'categories' => self::CATEGORIES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('seguridad/glosario/create', [
            'categories' => self::CATEGORIES,
        ]);
    }

    public function store(StoreGlossaryTermRequest $request): RedirectResponse
    {
        GlossaryTerm::create([
            ...$request->validated(),
            'source' => 'manual',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('seguridad.glosario.index')->with('success', 'Término creado correctamente.');
    }

    public function show(GlossaryTerm $glosario): Response
    {
        return Inertia::render('seguridad/glosario/show', [
            'term' => $glosario,
        ]);
    }

    public function edit(GlossaryTerm $glosario): Response
    {
        return Inertia::render('seguridad/glosario/create', [
            'term' => $glosario,
            'categories' => self::CATEGORIES,
        ]);
    }

    public function update(StoreGlossaryTermRequest $request, GlossaryTerm $glosario): RedirectResponse
    {
        $glosario->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('seguridad.glosario.index')->with('success', 'Término actualizado correctamente.');
    }

    public function destroy(GlossaryTerm $glosario): RedirectResponse
    {
        $glosario->delete();

        return redirect()->route('seguridad.glosario.index')->with('success', 'Término eliminado correctamente.');
    }
}
