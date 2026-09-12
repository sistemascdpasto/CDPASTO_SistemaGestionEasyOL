<?php

namespace App\Http\Controllers\Gente;

use App\Http\Controllers\Controller;
use App\Http\Requests\Gente\ImportarAusentismoRequest;
use App\Models\Gente\Ausentismo;
use App\Services\Gente\AusentismoImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AusentismoController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim($request->input('search', ''));
        $grupo = trim($request->input('grupo', ''));
        $permiso = trim($request->input('permiso', ''));
        $fechaDesde = trim($request->input('fecha_desde', ''));
        $fechaHasta = trim($request->input('fecha_hasta', ''));

        $query = Ausentismo::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('identificador', 'like', "%{$search}%")
                  ->orWhere('turno', 'like', "%{$search}%");
            });
        }

        if ($grupo !== '') {
            $query->where('grupo', $grupo);
        }

        if ($permiso !== '') {
            $query->where('permiso', $permiso);
        }

        if ($fechaDesde !== '') {
            $query->where('fecha', '>=', $fechaDesde);
        }

        if ($fechaHasta !== '') {
            $query->where('fecha', '<=', $fechaHasta);
        }

        $registros = $query->orderBy('fecha', 'desc')
            ->orderBy('apellidos')
            ->paginate(15)
            ->withQueryString();

        // KPIs
        $totalRegistros = Ausentismo::count();
        $totalColaboradores = Ausentismo::distinct('identificador')->count('identificador');
        $totalConPermiso = Ausentismo::whereNotNull('permiso')
            ->where('permiso', '!=', '')
            ->where('permiso', '!=', '0')
            ->count();
        $totalConAtraso = Ausentismo::where(function ($q) {
            $q->where(function ($q2) {
                $q2->whereNotNull('atraso_1')
                   ->where('atraso_1', '!=', '')
                   ->where('atraso_1', '!=', '00:00:00')
                   ->where('atraso_1', '!=', '00:00')
                   ->where('atraso_1', '!=', '0');
            })->orWhere(function ($q3) {
                $q3->whereNotNull('atraso_2')
                   ->where('atraso_2', '!=', '')
                   ->where('atraso_2', '!=', '00:00:00')
                   ->where('atraso_2', '!=', '00:00')
                   ->where('atraso_2', '!=', '0');
            });
        })->count();

        // Opciones de filtros
        $grupos = Ausentismo::whereNotNull('grupo')->distinct()->pluck('grupo')->filter()->values();
        $permisos = Ausentismo::whereNotNull('permiso')->distinct()->pluck('permiso')->filter()->values();

        return Inertia::render('gente/ausentismo/index', [
            'registros' => $registros,
            'filters' => [
                'search' => $search,
                'grupo' => $grupo,
                'permiso' => $permiso,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
            'options' => [
                'grupos' => $grupos,
                'permisos' => $permisos,
            ],
            'kpis' => [
                'total' => $totalRegistros,
                'total_colaboradores' => $totalColaboradores,
                'total_con_permiso' => $totalConPermiso,
                'total_con_atraso' => $totalConAtraso,
            ],
        ]);
    }

    public function importar(ImportarAusentismoRequest $request, AusentismoImportService $service): RedirectResponse
    {
        $archivo = $request->file('archivo');
        $resultado = $service->importar($archivo->getRealPath());

        $msg = "Importación de Ausentismo completada: {$resultado['creados']} creados, {$resultado['actualizados']} actualizados, {$resultado['procesados']} procesados en total.";
        if ($resultado['errores'] > 0) {
            $msg .= " Se presentaron {$resultado['errores']} errores en filas.";
        }

        return redirect()->back()->with('success', $msg);
    }

    public function exportar(Request $request): StreamedResponse
    {
        $search = trim($request->input('search', ''));
        $grupo = trim($request->input('grupo', ''));
        $permiso = trim($request->input('permiso', ''));
        $fechaDesde = trim($request->input('fecha_desde', ''));
        $fechaHasta = trim($request->input('fecha_hasta', ''));

        $query = Ausentismo::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('nombres', 'like', "%{$search}%")
                  ->orWhere('apellidos', 'like', "%{$search}%")
                  ->orWhere('identificador', 'like', "%{$search}%")
                  ->orWhere('turno', 'like', "%{$search}%");
            });
        }

        if ($grupo !== '') {
            $query->where('grupo', $grupo);
        }

        if ($permiso !== '') {
            $query->where('permiso', $permiso);
        }

        if ($fechaDesde !== '') {
            $query->where('fecha', '>=', $fechaDesde);
        }

        if ($fechaHasta !== '') {
            $query->where('fecha', '<=', $fechaHasta);
        }

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="ausentismo_' . date('Y-m-d_H-i') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Apellidos', 'Nombres', 'Identificador', 'Grupo', 'Fecha', 'Permiso', 'Turno',
                'Entró 1', 'Atraso 1', 'Salió 1', 'Adelanto 1',
                'Entró 2', 'Atraso 2', 'Salió 2', 'Adelanto 2',
            ], ';');

            $query->orderBy('fecha', 'desc')->chunk(500, function ($filas) use ($handle) {
                foreach ($filas as $f) {
                    fputcsv($handle, [
                        $f->apellidos,
                        $f->nombres,
                        $f->identificador,
                        $f->grupo,
                        $f->fecha ? $f->fecha->format('Y-m-d') : '',
                        $f->permiso,
                        $f->turno,
                        $f->entro_1,
                        $f->atraso_1,
                        $f->salio_1,
                        $f->adelanto_1,
                        $f->entro_2,
                        $f->atraso_2,
                        $f->salio_2,
                        $f->adelanto_2,
                    ], ';');
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    public function limpiar(): RedirectResponse
    {
        Ausentismo::truncate();
        return redirect()->back()->with('success', 'Todos los registros de Ausentismo fueron eliminados.');
    }
}
