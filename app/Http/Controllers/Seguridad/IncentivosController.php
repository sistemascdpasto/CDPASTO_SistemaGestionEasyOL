<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seguridad\ImportarIncentivosRequest;
use App\Models\Seguridad\Incentivo;
use App\Services\Seguridad\IncentivosImportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncentivosController extends Controller
{
    /**
     * Lista paginada de incentivos con filtros opcionales por mes y colaborador.
     */
    public function index(): Response
    {
        $filtros = [
            'mes'          => request('mes', ''),
            'colaborador'  => request('colaborador', ''),
        ];

        $incentivos = Incentivo::with('colaborador')
            ->when($filtros['mes'], fn ($q) => $q->where('mes', 'like', '%'.$filtros['mes'].'%'))
            ->when($filtros['colaborador'], function ($q) use ($filtros) {
                $q->whereHas('colaborador', function ($q2) use ($filtros) {
                    $q2->where('nombres', 'like', '%'.$filtros['colaborador'].'%')
                        ->orWhere('apellidos', 'like', '%'.$filtros['colaborador'].'%')
                        ->orWhere('cedula', 'like', '%'.$filtros['colaborador'].'%');
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('seguridad/incentivos/index', [
            'incentivos' => $incentivos,
            'filters'    => $filtros,
        ]);
    }

    /**
     * Importa registros de incentivos desde uno o varios archivos Excel.
     * Solo se persisten las filas cuya cédula exista en la tabla de colaboradores.
     */
    public function store(ImportarIncentivosRequest $request, IncentivosImportService $service): RedirectResponse
    {
        $rutas = collect($request->file('archivos'))
            ->map(fn ($archivo) => $archivo->getRealPath())
            ->all();

        $resultado = $service->importar($rutas);

        $mensaje = "Importación completa ({$resultado['archivos_procesados']} archivo(s)): "
            ."{$resultado['creados']} creados, "
            ."{$resultado['actualizados']} actualizados, "
            ."{$resultado['omitidos_sin_colaborador']} omitidos (cédula sin colaborador), "
            ."{$resultado['errores']} con error.";

        $tipo = match (true) {
            $resultado['creados'] === 0 && $resultado['actualizados'] === 0 => 'error',
            $resultado['omitidos_sin_colaborador'] > 0 || $resultado['errores'] > 0 => 'warning',
            default => 'success',
        };

        return to_route('seguridad.incentivos.index')->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }
}
