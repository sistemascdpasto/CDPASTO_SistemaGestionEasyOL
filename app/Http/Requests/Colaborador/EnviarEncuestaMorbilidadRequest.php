<?php

namespace App\Http\Requests\Colaborador;

use App\Models\Seguridad\EncuestaMorbilidad;
use App\Services\Colaborador\MorbilidadCatalogoService;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Foundation\Http\FormRequest;

class EnviarEncuestaMorbilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var EncuestaMorbilidad $encuesta */
        $encuesta = $this->route('encuestaMorbilidad');
        $colaborador = $this->user()->colaborador;

        return $colaborador
            && $encuesta->colaborador_id === $colaborador->id
            && $encuesta->estado === EncuestaMorbilidad::ESTADO_BORRADOR;
    }

    public function rules(): array
    {
        return [
            // ── Respuestas de secciones 2-10 ─────────────────────────────
            'respuestas'                          => ['present', 'array'],
            'respuestas.*.numero'                 => ['required', 'integer'],
            'respuestas.*.valor'                  => ['nullable', 'string'],
            'respuestas.*.detalle'                => ['nullable', 'string'],
            // ── Campos obligatorios del Paso 1 ────────────────────────────
            'paso1'                               => ['required', 'array'],
            'paso1.empresa'                       => ['required', 'string', 'max:50'],
            'paso1.correo_electronico'            => ['required', 'email', 'max:120'],
            'paso1.edad'                          => ['required', 'integer', 'min:14', 'max:100'],
            'paso1.estado_civil'                  => ['required', 'string', 'max:50'],
            'paso1.tiene_hijos'                   => ['required', 'string', 'in:Si,No'],
            'paso1.hijos'                         => ['nullable', 'array'],
            'paso1.hijos.*.nombre'                => ['required_with:paso1.hijos', 'string', 'max:80'],
            'paso1.hijos.*.edad'                  => ['required_with:paso1.hijos', 'integer', 'min:0'],
            'paso1.personas_a_cargo'              => ['required', 'string', 'in:Si,No'],
            'paso1.personas_cargo_detalle'        => ['nullable', 'array'],
            'paso1.personas_cargo_detalle.*.tipo' => ['required_with:paso1.personas_cargo_detalle', 'string', 'max:30'],
            'paso1.personas_cargo_detalle.*.edad' => ['required_with:paso1.personas_cargo_detalle', 'integer', 'min:0'],
            'paso1.nivel_escolaridad'             => ['required', 'string', 'max:60'],
            'paso1.estrato_socioeconomico'        => ['required', 'string', 'max:40'],
            'paso1.tenencia_vivienda'             => ['required', 'string', 'max:60'],
            'paso1.ciudad_residencia'             => ['required', 'string', 'max:100'],
            'paso1.direccion_residencia'          => ['required', 'string', 'max:200'],
            'paso1.tipo_contratacion'             => ['required', 'string', 'max:80'],
            'paso1.cargo_paso1'                   => ['required', 'string', 'max:100'],
            'paso1.area_paso1'                    => ['required', 'string', 'max:100'],
            'paso1.antiguedad_empresa'            => ['required', 'string', 'max:40'],
            'paso1.antiguedad_cargo'              => ['required', 'string', 'max:40'],
            'paso1.duracion_contrato'             => ['required', 'string', 'max:40'],
            'paso1.turno'                         => ['required', 'string', 'max:20'],
            'paso1.promedio_ingresos'             => ['required', 'string', 'max:60'],
        ];
    }

    public function messages(): array
    {
        return [
            'paso1.empresa.required'            => 'El campo Empresa es obligatorio.',
            'paso1.correo_electronico.required'  => 'El correo electrónico es obligatorio.',
            'paso1.correo_electronico.email'     => 'Ingresa un correo electrónico válido.',
            'paso1.edad.required'               => 'La edad es obligatoria.',
            'paso1.estado_civil.required'        => 'El estado civil es obligatorio.',
            'paso1.tiene_hijos.required'         => 'Indica si tienes hijos.',
            'paso1.personas_a_cargo.required'    => 'Indica si tienes personas a cargo.',
            'paso1.nivel_escolaridad.required'   => 'El nivel de escolaridad es obligatorio.',
            'paso1.estrato_socioeconomico.required' => 'El estrato socioeconómico es obligatorio.',
            'paso1.tenencia_vivienda.required'   => 'La tenencia de vivienda es obligatoria.',
            'paso1.ciudad_residencia.required'   => 'La ciudad de residencia es obligatoria.',
            'paso1.direccion_residencia.required'=> 'La dirección de residencia es obligatoria.',
            'paso1.tipo_contratacion.required'   => 'El tipo de contratación es obligatorio.',
            'paso1.cargo_paso1.required'         => 'El cargo es obligatorio.',
            'paso1.area_paso1.required'          => 'El área es obligatoria.',
            'paso1.antiguedad_empresa.required'  => 'La antigüedad en la empresa es obligatoria.',
            'paso1.antiguedad_cargo.required'    => 'La antigüedad en el cargo es obligatoria.',
            'paso1.duracion_contrato.required'   => 'La duración del contrato es obligatoria.',
            'paso1.turno.required'               => 'El turno es obligatorio.',
            'paso1.promedio_ingresos.required'   => 'El promedio de ingresos es obligatorio.',
        ];
    }

    public function withValidator(ValidatorContract $validator): void
    {
        $validator->after(function (ValidatorContract $validator) {
            $catalogo = new MorbilidadCatalogoService();
            $planas = $catalogo->preguntasPlanas();

            $respuestasPorNumero = collect($this->input('respuestas', []))
                ->keyBy(fn ($respuesta) => (int) ($respuesta['numero'] ?? 0));

            foreach ($planas as $numero => $pregunta) {
                $respuesta = $respuestasPorNumero->get($numero);
                $valor = $respuesta['valor'] ?? null;
                $detalle = trim((string) ($respuesta['detalle'] ?? ''));

                // RN-03/RN-04/RN-05: "No aplica" es el valor por defecto de
                // las preguntas `aplica_detalle` — si no llegó respuesta
                // para una de estas, no se exige explícitamente (el
                // frontend ya la envía marcada, pero esto evita que un
                // cliente distinto, o un bug de inicialización, bloquee el
                // envío por algo que visualmente ya está respondido).
                if ($pregunta['tipo'] === 'aplica_detalle' && ! $respuesta) {
                    $valor = 'No aplica';
                }

                if ($pregunta['obligatorio']) {
                    $permitidos = $catalogo->valoresPermitidosPorTipo($pregunta['tipo']);

                    // Para tipos sin lista cerrada (numero, checkbox_multiple,
                    // actividades_salud, segmento_corporal) solo exigimos
                    // que el valor no esté vacío.
                    if (empty($permitidos)) {
                        if ($valor === null || $valor === '') {
                            $validator->errors()->add("respuestas.{$numero}.valor", 'Debes responder esta pregunta.');
                        }
                        continue;
                    }

                    if (! in_array($valor, $permitidos, true)) {
                        $validator->errors()->add("respuestas.{$numero}.valor", 'Debes responder esta pregunta.');

                        continue;
                    }
                }

                $requiereDetalle = ($pregunta['tipo'] === 'si_no_detalle' && $valor === 'Si')
                    || ($pregunta['tipo'] === 'aplica_detalle' && $valor === 'Aplica');

                if ($requiereDetalle && $detalle === '') {
                    $validator->errors()->add("respuestas.{$numero}.detalle", 'Por favor especifica más detalle.');
                }
            }
        });
    }
}
