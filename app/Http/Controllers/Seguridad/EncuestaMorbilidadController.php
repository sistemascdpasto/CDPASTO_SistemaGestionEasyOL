<?php

namespace App\Http\Controllers\Seguridad;

use App\Http\Controllers\Controller;
use App\Models\Seguridad\EncuestaMorbilidad;
use App\Services\Colaborador\MorbilidadCatalogoService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EncuestaMorbilidadController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->only(['colaborador', 'mes', 'anio']);

        $encuestas = EncuestaMorbilidad::query()
            ->with('colaborador:id,nombres,apellidos,cedula,area,cargo')
            ->where('estado', EncuestaMorbilidad::ESTADO_COMPLETADA)
            ->when($filtros['colaborador'] ?? null, function ($query, $texto) {
                $query->whereHas('colaborador', function ($q) use ($texto) {
                    $q->where('nombres', 'like', "%{$texto}%")
                        ->orWhere('apellidos', 'like', "%{$texto}%")
                        ->orWhere('cedula', 'like', "%{$texto}%");
                });
            })
            ->when($filtros['mes'] ?? null, fn ($query, $mes) => $query->whereMonth('enviado_en', $mes))
            ->when($filtros['anio'] ?? null, fn ($query, $anio) => $query->whereYear('enviado_en', $anio))
            ->latest('enviado_en')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('seguridad/encuestas-morbilidad/index', [
            'encuestas' => $encuestas,
            'filters' => $filtros,
        ]);
    }

    /**
     * Detalle completo, incluida la sección psicosocial sensible (9) —
     * RN-09 ya queda satisfecha porque esta pantalla vive dentro del grupo
     * de rutas `role:Administrador|Seguridad` (routes/seguridad.php); no
     * hay ningún otro visor de encuestas ajenas en el sistema.
     */
    public function show(EncuestaMorbilidad $encuestaMorbilidad): Response
    {
        $encuestaMorbilidad->load('colaborador');

        $respuestas = $encuestaMorbilidad->respuestas()->get()
            ->keyBy('numero_pregunta')
            ->map(fn ($respuesta) => ['valor' => $respuesta->valor, 'detalle' => $respuesta->detalle]);

        return Inertia::render('seguridad/encuestas-morbilidad/show', [
            'encuesta' => [
                'id' => $encuestaMorbilidad->id,
                'fecha_hora' => $encuestaMorbilidad->fecha_hora,
                'enviado_en' => $encuestaMorbilidad->enviado_en,
            ],
            'colaborador' => $encuestaMorbilidad->colaborador,
            'paso1' => [
                'empresa'                => $encuestaMorbilidad->empresa,
                'correo_electronico'     => $encuestaMorbilidad->correo_electronico,
                'edad'                   => $encuestaMorbilidad->edad,
                'estado_civil'           => $encuestaMorbilidad->estado_civil,
                'tiene_hijos'            => $encuestaMorbilidad->tiene_hijos,
                'hijos'                  => $encuestaMorbilidad->hijos ?? [],
                'personas_a_cargo'       => $encuestaMorbilidad->personas_a_cargo,
                'personas_cargo_detalle' => $encuestaMorbilidad->personas_cargo_detalle ?? [],
                'nivel_escolaridad'      => $encuestaMorbilidad->nivel_escolaridad,
                'estrato_socioeconomico' => $encuestaMorbilidad->estrato_socioeconomico,
                'tenencia_vivienda'      => $encuestaMorbilidad->tenencia_vivienda,
                'ciudad_residencia'      => $encuestaMorbilidad->ciudad_residencia,
                'direccion_residencia'   => $encuestaMorbilidad->direccion_residencia,
                'tipo_contratacion'      => $encuestaMorbilidad->tipo_contratacion,
                'cargo_paso1'            => $encuestaMorbilidad->cargo_paso1,
                'area_paso1'             => $encuestaMorbilidad->area_paso1,
                'antiguedad_empresa'     => $encuestaMorbilidad->antiguedad_empresa,
                'antiguedad_cargo'       => $encuestaMorbilidad->antiguedad_cargo,
                'duracion_contrato'      => $encuestaMorbilidad->duracion_contrato,
                'turno'                  => $encuestaMorbilidad->turno,
                'promedio_ingresos'      => $encuestaMorbilidad->promedio_ingresos,
            ],
            'secciones' => (new MorbilidadCatalogoService())->secciones(),
            'respuestas' => $respuestas,
        ]);
    }
}
