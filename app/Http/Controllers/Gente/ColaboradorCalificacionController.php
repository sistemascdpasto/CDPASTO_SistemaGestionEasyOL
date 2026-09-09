<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\ImportarColaboradorCalificacionesRequest;
use App\Models\Gente\ColaboradorCalificacion;
use App\Services\Gente\ColaboradorCalificacionImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ColaboradorCalificacionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $modulo = $request->string('modulo')->trim()->toString();
        $centro = $request->string('centro_distribucion')->trim()->toString();
        $cargo = $request->string('cargo')->trim()->toString();

        $query = ColaboradorCalificacion::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('colaborador', 'like', "%{$search}%")
                  ->orWhere('identificacion', 'like', "%{$search}%")
                  ->orWhere('modulo', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%");
            });
        }

        if ($modulo !== '') {
            $query->where('modulo', $modulo);
        }

        if ($centro !== '') {
            $query->where('centro_distribucion', $centro);
        }

        if ($cargo !== '') {
            $query->where('cargo', $cargo);
        }

        $registros = (clone $query)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        // Resumen KPI global (sin paginar)
        $totalRegistros = ColaboradorCalificacion::count();
        $totalColaboradores = ColaboradorCalificacion::distinct('identificacion')->count('identificacion');
        $totalModulos = ColaboradorCalificacion::distinct('modulo')->count('modulo');
        $promedioNota = round((float) (ColaboradorCalificacion::avg('nota_modulo') ?? 0), 2);

        // Opciones únicas para selectores de filtro
        $modulosOpt = ColaboradorCalificacion::whereNotNull('modulo')
            ->where('modulo', '!=', '')
            ->distinct()
            ->orderBy('modulo')
            ->pluck('modulo');

        $centrosOpt = ColaboradorCalificacion::whereNotNull('centro_distribucion')
            ->where('centro_distribucion', '!=', '')
            ->distinct()
            ->orderBy('centro_distribucion')
            ->pluck('centro_distribucion');

        $cargosOpt = ColaboradorCalificacion::whereNotNull('cargo')
            ->where('cargo', '!=', '')
            ->distinct()
            ->orderBy('cargo')
            ->pluck('cargo');

        return Inertia::render('gente/calificaciones/index', [
            'calificaciones' => $registros,
            'resumen' => [
                'total_registros' => $totalRegistros,
                'total_colaboradores' => $totalColaboradores,
                'total_modulos' => $totalModulos,
                'promedio_nota' => $promedioNota,
            ],
            'filtros' => [
                'search' => $search,
                'modulo' => $modulo,
                'centro_distribucion' => $centro,
                'cargo' => $cargo,
            ],
            'catalogos' => [
                'modulos' => $modulosOpt,
                'centros_distribucion' => $centrosOpt,
                'cargos' => $cargosOpt,
            ],
        ]);
    }

    public function importar(ImportarColaboradorCalificacionesRequest $request, ColaboradorCalificacionImportService $service): RedirectResponse
    {
        $resultado = $service->importar($request->file('archivo')->getRealPath());

        $mensaje = "Importación de calificaciones completada: {$resultado['creados']} creadas, "
            ."{$resultado['actualizados']} actualizadas, "
            ."{$resultado['errores']} con error.";

        $tipo = match (true) {
            $resultado['procesados'] === 0 => 'error',
            $resultado['errores'] > 0 => 'warning',
            default => 'success',
        };

        return redirect()->route('gente.calificaciones.index')->with('status', ['message' => $mensaje, 'type' => $tipo]);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $query = ColaboradorCalificacion::query();

        if ($request->filled('modulo')) {
            $query->where('modulo', $request->string('modulo'));
        }
        if ($request->filled('centro_distribucion')) {
            $query->where('centro_distribucion', $request->string('centro_distribucion'));
        }
        if ($request->filled('cargo')) {
            $query->where('cargo', $request->string('cargo'));
        }

        $registros = $query->orderBy('colaborador')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="calificaciones_modulos.csv"',
        ];

        $callback = function () use ($registros) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'COLABORADOR',
                'IDENTIFICACIÓN',
                'CARGO',
                'CENTRO DISTRIBUCIÓN',
                'ID MÓDULO',
                'MÓDULO',
                'NOTA MÓDULO',
            ]);

            foreach ($registros as $row) {
                fputcsv($file, [
                    $row->colaborador,
                    $row->identificacion,
                    $row->cargo,
                    $row->centro_distribucion,
                    $row->modulo_id_externo,
                    $row->modulo,
                    $row->nota_modulo,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function limpiar(): RedirectResponse
    {
        ColaboradorCalificacion::truncate();

        return redirect()->route('gente.calificaciones.index')->with('status', [
            'message' => 'Todas las calificaciones han sido eliminadas.',
            'type' => 'success',
        ]);
    }
}
