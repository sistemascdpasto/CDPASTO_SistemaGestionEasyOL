<?php

namespace App\Http\Controllers\Seguridad;

use App\Exports\Seguridad\EvaluacionesMedicasCompletaExport;
use App\Exports\Seguridad\EvaluacionesMedicasExport;
use App\Http\Controllers\Controller;
use App\Models\Seguridad\EvaluacionMedica;
use App\Services\Seguridad\EvaluacionMedicaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class EvaluacionMedicaExportController extends Controller
{
    public function basica(Request $request, EvaluacionMedicaService $service)
    {
        $evaluaciones = $this->filtrar($request)->get();

        return Excel::download(new EvaluacionesMedicasExport($evaluaciones, $service), 'examenes-medicos-'.now()->format('Y-m-d').'.xlsx');
    }

    public function completa(Request $request, EvaluacionMedicaService $service)
    {
        $evaluaciones = $this->filtrar($request)->get();

        return Excel::download(new EvaluacionesMedicasCompletaExport($evaluaciones, $service), 'examenes-medicos-completo-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * HU-041 CA-041.1/041.6: mismos filtros exactos que la bandeja
     * (`EvaluacionMedicaController::index`), para que el Excel sea 1:1 con
     * lo que se ve en pantalla.
     */
    private function filtrar(Request $request): Builder
    {
        $tipoEvaluacion = $request->string('tipo_evaluacion')->toString();
        $estado = $request->string('estado')->toString();
        $verHistorial = $request->boolean('ver_historial');
        $colaboradorTexto = $request->string('colaborador')->toString();
        $identificacion = $request->string('identificacion')->toString();
        $cargo = $request->string('cargo')->toString();
        $centro = $request->string('centro')->toString();

        return EvaluacionMedica::query()
            ->with(['colaborador', 'conceptoAptitud', 'examenes', 'recomendaciones'])
            ->when(! $verHistorial, fn ($q) => $q->whereNotIn('estado', ['terminada', 'ejecutado']))
            ->when($tipoEvaluacion, fn ($q, $v) => $q->where('tipo_evaluacion', $v))
            ->when($estado, fn ($q, $v) => $q->where('estado', $v))
            ->when($colaboradorTexto, function ($q, $v) {
                $q->whereHas('colaborador', function ($qq) use ($v) {
                    $qq->where('nombres', 'like', "%{$v}%")->orWhere('apellidos', 'like', "%{$v}%");
                });
            })
            ->when($identificacion, function ($q, $v) {
                $q->whereHas('colaborador', fn ($qq) => $qq->where('cedula', 'like', "%{$v}%"));
            })
            ->when($cargo, function ($q, $v) {
                $q->whereHas('colaborador', fn ($qq) => $qq->where('cargo', $v));
            })
            ->when($centro, function ($q, $v) {
                $q->whereHas('colaborador', fn ($qq) => $qq->where('centro', $v));
            })
            ->latest('fecha_evaluacion');
    }
}
