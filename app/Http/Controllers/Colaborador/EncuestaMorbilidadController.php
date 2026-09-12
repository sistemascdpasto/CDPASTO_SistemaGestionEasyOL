<?php

namespace App\Http\Controllers\Colaborador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Colaborador\EnviarEncuestaMorbilidadRequest;
use App\Models\Seguridad\Colaborador;
use App\Models\Seguridad\EncuestaMorbilidad;
use App\Services\Colaborador\MorbilidadCatalogoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EncuestaMorbilidadController extends Controller
{
    public function create(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        // Retoma el borrador abierto más reciente (HU general CA-01:
        // "conserva un borrador para continuar posteriormente"), o abre uno
        // nuevo — no se bloquea iniciar una encuesta nueva si la anterior ya
        // quedó `completada`.
        $encuesta = EncuestaMorbilidad::where('colaborador_id', $colaborador->id)
            ->where('estado', EncuestaMorbilidad::ESTADO_BORRADOR)
            ->latest('id')
            ->first();

        if (! $encuesta) {
            $encuesta = EncuestaMorbilidad::create([
                'colaborador_id' => $colaborador->id,
                'estado' => EncuestaMorbilidad::ESTADO_BORRADOR,
                'fecha_hora' => now(),
            ]);
        }

        // Cargar portadas de secciones desde la BD
        $portadas = \App\Models\Seguridad\EncuestaMorbilidadSeccion::all()
            ->keyBy('numero')
            ->map(fn ($s) => $s->imagen_portada_url);

        return Inertia::render('colaborador/encuesta-morbilidad/index', [
            'encuestaId' => $encuesta->id,
            'colaborador' => [
                'nombre_completo'   => $colaborador->nombre_completo,
                'cedula'            => $colaborador->cedula,
                'area'              => $colaborador->area,
                'cargo'             => $colaborador->cargo,
                'correo'            => $colaborador->correo,
                'edad'              => $colaborador->edad,
                'estado_civil'      => $colaborador->estado_civil,
                'ciudad_residencia' => $colaborador->ciudad_residencia,
                'estrato'           => $colaborador->estrato,
                'turno'             => $colaborador->turno,
                'tipo_contrato'     => $colaborador->tipo_contrato,
            ],
            'fechaHora'  => $encuesta->fecha_hora,
            'secciones'  => (new MorbilidadCatalogoService())->secciones(),
            'respuestas' => $this->respuestasIndexadas($encuesta),
            'portadas'   => $portadas,
            // Datos del paso 1 ya guardados (para reanudar borrador)
            'paso1' => [
                'empresa'                => $encuesta->empresa,
                'correo_electronico'     => $encuesta->correo_electronico,
                'edad'                   => $encuesta->edad,
                'estado_civil'           => $encuesta->estado_civil,
                'tiene_hijos'            => $encuesta->tiene_hijos,
                'hijos'                  => $encuesta->hijos ?? [],
                'personas_a_cargo'       => $encuesta->personas_a_cargo,
                'personas_cargo_detalle' => $encuesta->personas_cargo_detalle ?? [],
                'nivel_escolaridad'      => $encuesta->nivel_escolaridad,
                'estrato_socioeconomico' => $encuesta->estrato_socioeconomico,
                'tenencia_vivienda'      => $encuesta->tenencia_vivienda,
                'ciudad_residencia'      => $encuesta->ciudad_residencia,
                'direccion_residencia'   => $encuesta->direccion_residencia,
                'tipo_contratacion'      => $encuesta->tipo_contratacion,
                'cargo_paso1'            => $encuesta->cargo_paso1,
                'area_paso1'             => $encuesta->area_paso1,
                'antiguedad_empresa'     => $encuesta->antiguedad_empresa,
                'antiguedad_cargo'       => $encuesta->antiguedad_cargo,
                'duracion_contrato'      => $encuesta->duracion_contrato,
                'turno'                  => $encuesta->turno,
                'promedio_ingresos'      => $encuesta->promedio_ingresos,
            ],
        ]);
    }

    public function guardar(Request $request, EncuestaMorbilidad $encuestaMorbilidad): RedirectResponse
    {
        $colaborador = $this->colaboradorDeOFallar($request);
        $this->autorizarPropiaYBorrador($encuestaMorbilidad, $colaborador);

        $catalogo = (new MorbilidadCatalogoService())->preguntasPlanas();

        $validarPaso1 = $request->boolean('validar_paso1');
        $reqRule = $validarPaso1 ? 'required' : 'nullable';
        $reqWithHijos = $validarPaso1 ? 'required_with:paso1.hijos' : 'nullable';
        $reqWithCargo = $validarPaso1 ? 'required_with:paso1.personas_cargo_detalle' : 'nullable';

        $rules = [
            // ── Respuestas de secciones 2-10 ─────────────────────────────
            'respuestas'                          => ['present', 'array'],
            'respuestas.*.numero'                 => ['required', 'integer', Rule::in(array_keys($catalogo))],
            'respuestas.*.valor'                  => ['nullable', 'string'],
            'respuestas.*.detalle'                => ['nullable', 'string'],
            // ── Campos del Paso 1 ─────────────────────────────────────────
            'paso1'                               => [$validarPaso1 ? 'required' : 'nullable', 'array'],
            'paso1.empresa'                       => [$reqRule, 'string', 'max:50'],
            'paso1.correo_electronico'            => [$reqRule, 'email', 'max:120'],
            'paso1.edad'                          => [$reqRule, 'integer', 'min:14', 'max:100'],
            'paso1.estado_civil'                  => [$reqRule, 'string', 'max:50'],
            'paso1.tiene_hijos'                   => [$reqRule, 'string', 'in:Si,No'],
            'paso1.hijos'                         => ['nullable', 'array'],
            'paso1.hijos.*.nombre'                => [$reqWithHijos, 'string', 'max:80'],
            'paso1.hijos.*.edad'                  => [$reqWithHijos, 'integer', 'min:0'],
            'paso1.personas_a_cargo'              => [$reqRule, 'string', 'in:Si,No'],
            'paso1.personas_cargo_detalle'        => ['nullable', 'array'],
            'paso1.personas_cargo_detalle.*.tipo' => [$reqWithCargo, 'string', 'max:30'],
            'paso1.personas_cargo_detalle.*.edad' => [$reqWithCargo, 'integer', 'min:0'],
            'paso1.nivel_escolaridad'             => [$reqRule, 'string', 'max:60'],
            'paso1.estrato_socioeconomico'        => [$reqRule, 'string', 'max:40'],
            'paso1.tenencia_vivienda'             => [$reqRule, 'string', 'max:60'],
            'paso1.ciudad_residencia'             => [$reqRule, 'string', 'max:100'],
            'paso1.direccion_residencia'          => [$reqRule, 'string', 'max:200'],
            'paso1.tipo_contratacion'             => [$reqRule, 'string', 'max:80'],
            'paso1.cargo_paso1'                   => [$reqRule, 'string', 'max:100'],
            'paso1.area_paso1'                    => [$reqRule, 'string', 'max:100'],
            'paso1.antiguedad_empresa'            => [$reqRule, 'string', 'max:40'],
            'paso1.antiguedad_cargo'              => [$reqRule, 'string', 'max:40'],
            'paso1.duracion_contrato'             => [$reqRule, 'string', 'max:40'],
            'paso1.turno'                         => [$reqRule, 'string', 'max:20'],
            'paso1.promedio_ingresos'             => [$reqRule, 'string', 'max:60'],
        ];

        $messages = [
            'paso1.empresa.required'               => 'El campo Empresa es obligatorio.',
            'paso1.correo_electronico.required'     => 'El correo electrónico es obligatorio.',
            'paso1.correo_electronico.email'        => 'Ingresa un correo electrónico válido.',
            'paso1.edad.required'                  => 'La edad es obligatoria.',
            'paso1.estado_civil.required'           => 'El estado civil es obligatorio.',
            'paso1.tiene_hijos.required'            => 'Indica si tienes hijos.',
            'paso1.personas_a_cargo.required'       => 'Indica si tienes personas a cargo.',
            'paso1.nivel_escolaridad.required'      => 'El nivel de escolaridad es obligatorio.',
            'paso1.estrato_socioeconomico.required' => 'El estrato socioeconómico es obligatorio.',
            'paso1.tenencia_vivienda.required'      => 'La tenencia de vivienda es obligatoria.',
            'paso1.ciudad_residencia.required'      => 'La ciudad de residencia es obligatoria.',
            'paso1.direccion_residencia.required'   => 'La dirección de residencia es obligatoria.',
            'paso1.tipo_contratacion.required'      => 'El tipo de contratación es obligatorio.',
            'paso1.cargo_paso1.required'            => 'El cargo es obligatorio.',
            'paso1.area_paso1.required'             => 'El área es obligatoria.',
            'paso1.antiguedad_empresa.required'     => 'La antigüedad en la empresa es obligatoria.',
            'paso1.antiguedad_cargo.required'       => 'La antigüedad en el cargo es obligatoria.',
            'paso1.duracion_contrato.required'      => 'La duración del contrato es obligatoria.',
            'paso1.turno.required'                  => 'El turno es obligatorio.',
            'paso1.promedio_ingresos.required'      => 'El promedio de ingresos es obligatorio.',
        ];

        $data = $request->validate($rules, $messages);

        // Guardar campos del paso 1 si vienen en el payload
        if (!empty($data['paso1'])) {
            $encuestaMorbilidad->update($data['paso1']);
        }

        $this->guardarRespuestas($encuestaMorbilidad, $data['respuestas']);

        return back()->with('status', 'Progreso guardado.');
    }

    public function store(EnviarEncuestaMorbilidadRequest $request, EncuestaMorbilidad $encuestaMorbilidad): RedirectResponse
    {
        // Guardar campos del paso 1
        if ($request->has('paso1')) {
            $encuestaMorbilidad->update($request->input('paso1'));
        }

        $this->guardarRespuestas($encuestaMorbilidad, $request->input('respuestas', []));

        $encuestaMorbilidad->update([
            'estado'     => EncuestaMorbilidad::ESTADO_COMPLETADA,
            'enviado_en' => now(),
        ]);

        return to_route('portal.encuesta-morbilidad.historial')
            ->with('status', 'Encuesta enviada correctamente. ¡Gracias por completarla!');
    }

    public function historial(Request $request): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);

        $encuestas = EncuestaMorbilidad::where('colaborador_id', $colaborador->id)
            ->latest('fecha_hora')
            ->paginate(15);

        return Inertia::render('colaborador/encuesta-morbilidad/historial', [
            'encuestas' => $encuestas,
        ]);
    }

    public function show(Request $request, EncuestaMorbilidad $encuestaMorbilidad): Response
    {
        $colaborador = $this->colaboradorDeOFallar($request);
        $this->autorizarPropia($encuestaMorbilidad, $colaborador);

        return Inertia::render('colaborador/encuesta-morbilidad/show', [
            'encuesta' => [
                'id' => $encuestaMorbilidad->id,
                'estado' => $encuestaMorbilidad->estado,
                'fecha_hora' => $encuestaMorbilidad->fecha_hora,
                'enviado_en' => $encuestaMorbilidad->enviado_en,
            ],
            'colaborador' => [
                'nombre_completo' => $colaborador->nombre_completo,
                'cedula' => $colaborador->cedula,
                'area' => $colaborador->area,
                'cargo' => $colaborador->cargo,
            ],
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
            'respuestas' => $this->respuestasIndexadas($encuestaMorbilidad),
        ]);
    }

    /**
     * @param  array<int, array{numero: int, valor: ?string, detalle: ?string}>  $respuestas
     */
    private function guardarRespuestas(EncuestaMorbilidad $encuestaMorbilidad, array $respuestas): void
    {
        foreach ($respuestas as $respuesta) {
            $encuestaMorbilidad->respuestas()->updateOrCreate(
                ['numero_pregunta' => $respuesta['numero']],
                ['valor' => $respuesta['valor'] ?? null, 'detalle' => $respuesta['detalle'] ?? null],
            );
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{valor: ?string, detalle: ?string}>
     */
    private function respuestasIndexadas(EncuestaMorbilidad $encuestaMorbilidad)
    {
        return $encuestaMorbilidad->respuestas()->get()
            ->keyBy('numero_pregunta')
            ->map(fn ($respuesta) => ['valor' => $respuesta->valor, 'detalle' => $respuesta->detalle]);
    }

    private function autorizarPropia(EncuestaMorbilidad $encuestaMorbilidad, Colaborador $colaborador): void
    {
        abort_unless($encuestaMorbilidad->colaborador_id === $colaborador->id, 403);
    }

    private function autorizarPropiaYBorrador(EncuestaMorbilidad $encuestaMorbilidad, Colaborador $colaborador): void
    {
        $this->autorizarPropia($encuestaMorbilidad, $colaborador);
        abort_unless($encuestaMorbilidad->estado === EncuestaMorbilidad::ESTADO_BORRADOR, 403);
    }

    private function colaboradorDeOFallar(Request $request): Colaborador
    {
        $colaborador = $request->user()->colaborador;

        if ($colaborador) {
            return $colaborador;
        }

        if ($request->user()->hasAnyRole(['Administrador', 'Seguridad'])) {
            return Colaborador::firstOrCreate(
                ['cedula' => 'ADM-' . sprintf('%06d', $request->user()->id)],
                [
                    'nombres'           => $request->user()->name,
                    'apellidos'         => '(Admin/Seguridad)',
                    'correo'            => $request->user()->email,
                    'activo'            => true,
                    'ciudad_residencia' => 'Bogotá',
                ]
            );
        }

        return abort(
            403,
            'Tu cuenta todavía no está vinculada a un registro de colaborador. Contacta a un administrador.'
        );
    }
}
