<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\ImportarDpoAcademyRequest;
use App\Models\Gente\DpoAcademy;
use App\Services\Gente\DpoAcademyImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DpoAcademyController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $region = trim($request->input('region', ''));
        $centro = trim($request->input('centro', ''));
        $negocio = trim($request->input('negocio', ''));
        $status = trim($request->input('status', ''));

        $query = DpoAcademy::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%")
                  ->orWhere('qr_safety', 'like', "%{$search}%")
                  ->orWhere('coronita', 'like', "%{$search}%");
            });
        }

        if ($region !== '') {
            $query->where('region', $region);
        }

        if ($centro !== '') {
            $query->where('centro', $centro);
        }

        if ($negocio !== '') {
            $query->where('negocio', $negocio);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $registros = $query->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        // KPIs generales
        $totalRegistros = DpoAcademy::count();
        $promedioCalificacion = round((float) (DpoAcademy::avg('calificacion') ?? 0), 2);
        $totalCoronitas = DpoAcademy::whereNotNull('coronita')
            ->where('coronita', '!=', '')
            ->where('coronita', '!=', '0')
            ->count();
        $totalCompletados = DpoAcademy::whereIn(DB::raw('LOWER(status)'), ['completado', 'completed', 'aprobado', 'ok', 'activo', 'active'])->count();

        // Opciones de filtro
        $regiones = DpoAcademy::whereNotNull('region')->distinct()->pluck('region')->filter()->values();
        $centros = DpoAcademy::whereNotNull('centro')->distinct()->pluck('centro')->filter()->values();
        $negocios = DpoAcademy::whereNotNull('negocio')->distinct()->pluck('negocio')->filter()->values();
        $statuses = DpoAcademy::whereNotNull('status')->distinct()->pluck('status')->filter()->values();

        return Inertia::render('gente/dpo-academy/index', [
            'registros' => $registros,
            'filters' => [
                'search' => $search,
                'region' => $region,
                'centro' => $centro,
                'negocio' => $negocio,
                'status' => $status,
            ],
            'options' => [
                'regiones' => $regiones,
                'centros' => $centros,
                'negocios' => $negocios,
                'statuses' => $statuses,
            ],
            'kpis' => [
                'total' => $totalRegistros,
                'promedio_calificacion' => $promedioCalificacion,
                'total_coronitas' => $totalCoronitas,
                'total_completados' => $totalCompletados,
            ],
        ]);
    }

    public function importar(ImportarDpoAcademyRequest $request, DpoAcademyImportService $service): RedirectResponse
    {
        $archivo = $request->file('archivo');
        $resultado = $service->importar($archivo->getRealPath());

        $msg = "Importación DPO Academy completada: {$resultado['creados']} creados, {$resultado['actualizados']} actualizados, {$resultado['procesados']} procesados en total.";
        if ($resultado['errores'] > 0) {
            $msg .= " Se presentaron {$resultado['errores']} errores.";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $search = trim($request->input('search', ''));
        $region = trim($request->input('region', ''));
        $centro = trim($request->input('centro', ''));
        $negocio = trim($request->input('negocio', ''));
        $status = trim($request->input('status', ''));

        $query = DpoAcademy::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('cargo', 'like', "%{$search}%")
                  ->orWhere('qr_safety', 'like', "%{$search}%")
                  ->orWhere('coronita', 'like', "%{$search}%");
            });
        }

        if ($region !== '') {
            $query->where('region', $region);
        }

        if ($centro !== '') {
            $query->where('centro', $centro);
        }

        if ($negocio !== '') {
            $query->where('negocio', $negocio);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="dpo_academy_' . date('Y-m-d_H-i') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 para Excel
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Region', 'Centro', 'Negocio', 'QR Safety', 'Nombre', 'Cargo', 'Coronita', 'Calificación', 'Status'], ';');

            $query->orderBy('nombre')->chunk(500, function ($filas) use ($handle) {
                foreach ($filas as $f) {
                    fputcsv($handle, [
                        $f->region,
                        $f->centro,
                        $f->negocio,
                        $f->qr_safety,
                        $f->nombre,
                        $f->cargo,
                        $f->coronita,
                        $f->calificacion,
                        $f->status,
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function limpiar(): RedirectResponse
    {
        DpoAcademy::truncate();
        return redirect()->back()->with('success', 'Todos los registros de DPO Academy fueron eliminados.');
    }
}
