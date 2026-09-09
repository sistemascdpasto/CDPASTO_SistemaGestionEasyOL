<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\StoreLlamadoAtencionRequest;
use App\Models\Seguridad\Colaborador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class LlamadoAtencionController extends Controller
{
    public function store(StoreLlamadoAtencionRequest $request, Colaborador $colaborador): RedirectResponse
    {
        $colaborador->llamadosAtencion()->create([
            'observacion' => $request->validated('observacion'),
            'path' => $request->hasFile('documento') ? $request->file('documento')->store('colaboradores/documentos', 'public') : null,
            'registrado_por_id' => $request->user()->id,
            'fecha_hora' => Carbon::now(),
        ]);

        return back()->with('status', 'Llamado de atención registrado correctamente.');
    }
}
