<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\ImportarIncentivosRequest;
use App\Models\Seguridad\Incentivo;
use App\Services\Gente\IncentivosImportService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IncentivosController extends Controller
{
    public function index(): Response
    {
        $filtros = [
            'mes'          => request('mes', ''),
            'colaborador'  => request('colaborador', ''),
            'cargo'        => request('cargo', ''),
        ];

        $incentivos = Incentivo::with('colaborador')
            ->when($filtros['mes'], fn ($q) => $q->where('mes', $filtros['mes']))
            ->when($filtros['cargo'], fn ($q) => $q->where('cargo', 'like', '%' . $filtros['cargo'] . '%'))
            ->when($filtros['colaborador'], function ($q) use ($filtros) {
                $term = $filtros['colaborador'];
                $q->where(function ($q2) use ($term) {
                    $q2->where('cedula', 'like', '%' . $term . '%')
                        ->orWhere('nombre', 'like', '%' . $term . '%')
                        ->orWhereHas('colaborador', function ($q3) use ($term) {
                            $q3->where('nombres', 'like', '%' . $term . '%')
                                ->orWhere('apellidos', 'like', '%' . $term . '%')
                                ->orWhere('cedula', 'like', '%' . $term . '%');
                        });
                });
            })
            ->orderByDesc('total_1')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $meses  = Incentivo::whereNotNull('mes')->distinct()->orderBy('mes')->pluck('mes');
        $cargos = Incentivo::whereNotNull('cargo')->distinct()->orderBy('cargo')->pluck('cargo');

        return Inertia::render('gente/incentivos/index', [
            'incentivos' => $incentivos,
            'filters'    => $filtros,
            'opciones'   => [
                'meses'  => $meses,
                'cargos' => $cargos,
            ],
        ]);
    }

    public function variable(): Response
    {
        $filtros = [
            'mes'         => request('mes', ''),
            'colaborador' => request('colaborador', ''),
            'cargo'       => request('cargo', ''),
        ];

        $incentivos = Incentivo::with('colaborador')
            ->when($filtros['mes'], fn ($q) => $q->where('mes', $filtros['mes']))
            ->when($filtros['cargo'], fn ($q) => $q->where('cargo', 'like', '%' . $filtros['cargo'] . '%'))
            ->when($filtros['colaborador'], function ($q) use ($filtros) {
                $term = $filtros['colaborador'];
                $q->where(function ($q2) use ($term) {
                    $q2->where('cedula', 'like', '%' . $term . '%')
                        ->orWhere('nombre', 'like', '%' . $term . '%')
                        ->orWhereHas('colaborador', function ($q3) use ($term) {
                            $q3->where('nombres', 'like', '%' . $term . '%')
                                ->orWhere('apellidos', 'like', '%' . $term . '%')
                                ->orWhere('cedula', 'like', '%' . $term . '%');
                        });
                });
            })
            ->orderByDesc('total_4')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        $meses  = Incentivo::whereNotNull('mes')->distinct()->orderBy('mes')->pluck('mes');
        $cargos = Incentivo::whereNotNull('cargo')->distinct()->orderBy('cargo')->pluck('cargo');

        return Inertia::render('gente/incentivos/variable', [
            'incentivos' => $incentivos,
            'filters'    => $filtros,
            'opciones'   => [
                'meses'  => $meses,
                'cargos' => $cargos,
            ],
        ]);
    }

    public function store(ImportarIncentivosRequest $request, IncentivosImportService $service): RedirectResponse
    {
        $rutas = collect($request->file('archivos'))
            ->map(fn ($archivo) => $archivo->getRealPath())
            ->all();

        $resultado = $service->importar($rutas);

        $mensaje = "Importación completa ({$resultado['archivos_procesados']} archivo(s)): "
            . "{$resultado['creados']} creados, "
            . "{$resultado['actualizados']} actualizados, "
            . "{$resultado['omitidos_sin_colaborador']} omitidos (cédula sin colaborador), "
            . "{$resultado['errores']} con error.";

        $tipo = match (true) {
            $resultado['creados'] === 0 && $resultado['actualizados'] === 0 => 'error',
            $resultado['omitidos_sin_colaborador'] > 0 || $resultado['errores'] > 0 => 'warning',
            default => 'success',
        };

        return to_route('gente.incentivos.index')->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }
}
