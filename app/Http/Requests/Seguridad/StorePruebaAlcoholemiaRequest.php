<?php

namespace App\Http\Requests\Seguridad;

use App\Models\Seguridad\Alcoholimetro;
use App\Models\Seguridad\PersonaExterna;
use App\Models\Seguridad\PruebaAlcoholemia;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePruebaAlcoholemiaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * `tipo_persona` distingue colaboradores de contratistas/personal
     * externo; los formularios anteriores no lo enviaban, así que por defecto
     * se asume colaborador.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('tipo_persona')) {
            $this->merge(['tipo_persona' => 'colaborador']);
        }

        if ($this->filled('externo_documento')) {
            $this->merge(['externo_documento' => preg_replace('/\s+/', '', (string) $this->input('externo_documento'))]);
        }
    }

    public function esExterno(): bool
    {
        return $this->input('tipo_persona') === 'externo';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $pruebaActual = $this->route('prueba');
        $alcoholimetroActualId = $pruebaActual?->alcoholimetro_id;

        $esExterno = $this->esExterno();

        return [
            'tipo_persona' => ['required', Rule::in(['colaborador', 'externo'])],
            'colaborador_id' => [Rule::requiredIf(! $esExterno), 'nullable', 'integer', Rule::exists('colaboradores', 'id')->whereNull('deleted_at')],
            'externo_documento' => [Rule::requiredIf($esExterno), 'nullable', 'string', 'max:30'],
            'externo_nombres' => [Rule::requiredIf($esExterno), 'nullable', 'string', 'max:255'],
            'externo_apellidos' => ['nullable', 'string', 'max:255'],
            'externo_empresa' => [Rule::requiredIf($esExterno), 'nullable', 'string', 'max:255'],
            'externo_tipo_vinculo' => [Rule::requiredIf($esExterno), 'nullable', Rule::in(array_keys(PersonaExterna::TIPOS_VINCULO))],
            'externo_cargo' => ['nullable', 'string', 'max:255'],
            'externo_telefono' => ['nullable', 'string', 'max:30'],
            'tipo' => ['required', Rule::in(['ingreso', 'aleatoria', 'salida'])],
            // Los externos no siempre están asignados a un turno de operación.
            'turno' => [Rule::requiredIf(! $esExterno), 'nullable', Rule::in(['A', 'B', 'C'])],
            'es_programacion' => ['boolean'],
            'programada_en' => [
                'required_if:es_programacion,1', 'nullable', 'date',
                function ($attribute, $value, $fail) use ($pruebaActual) {
                    if (! $value) {
                        return;
                    }

                    $sinCambios = $pruebaActual?->programada_en && Carbon::parse($value)->equalTo($pruebaActual->programada_en);

                    if (! $sinCambios && Carbon::parse($value)->isPast()) {
                        $fail('La fecha programada debe ser posterior al momento actual.');
                    }
                },
            ],
            'alcoholimetro_id' => [
                'required_unless:es_programacion,1', 'nullable', 'integer',
                Rule::exists('alcoholimetros', 'id')->where(function ($query) use ($alcoholimetroActualId) {
                    $query->where('estado', 'Disponible');

                    if ($alcoholimetroActualId) {
                        $query->orWhere('id', $alcoholimetroActualId);
                    }
                }),
            ],
            'resultado' => ['required_unless:es_programacion,1', 'nullable', 'numeric'],
            // 'accepted' es una regla implícita que siempre corre si el campo
            // está presente, incluso si 'required_unless' no lo exige — al
            // programar, el checkbox de consentimiento sigue viajando en el
            // formulario (Inertia manda booleans como '1'/'0'), así que sin
            // este Rule::when() un "0" tumbaba la validación aunque se
            // estuviera programando la prueba en vez de registrarla.
            'consentimiento_aceptado' => [
                'required_unless:es_programacion,1',
                Rule::when(! $this->boolean('es_programacion'), ['accepted']),
            ],
            'evidencia' => ['nullable', 'array'],
            'evidencia.*' => ['image', 'max:5120'],
            'evidencias' => ['nullable', 'array'],
            'evidencias.*' => ['mimes:pdf', 'max:5120'],
            'firma' => ['nullable', 'image', 'max:2048'],
            'observaciones' => ['nullable', 'string', 'max:2000'],
            'fecha_hora' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'externo_documento' => 'documento',
            'externo_nombres' => 'nombres',
            'externo_apellidos' => 'apellidos',
            'externo_empresa' => 'empresa',
            'externo_tipo_vinculo' => 'tipo de vínculo',
            'externo_cargo' => 'cargo',
            'externo_telefono' => 'teléfono',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->boolean('es_programacion') || ! $this->filled(['alcoholimetro_id', 'resultado'])) {
                return;
            }

            $alcoholimetro = Alcoholimetro::find($this->input('alcoholimetro_id'));

            if ($alcoholimetro && (
                (float) $this->input('resultado') < (float) $alcoholimetro->valor_min
                || (float) $this->input('resultado') > (float) $alcoholimetro->valor_max
            )) {
                $validator->errors()->add(
                    'resultado',
                    "El resultado debe estar entre {$alcoholimetro->valor_min} y {$alcoholimetro->valor_max} para este dispositivo."
                );
            }

            $prueba = $this->route('prueba');
            $fechaHora = $this->filled('fecha_hora')
                ? Carbon::parse($this->input('fecha_hora'))
                : ($prueba?->fecha_hora ?? Carbon::now());
            $horas = (int) config('seguridad.intervalo_minimo_horas');
            $ignorarId = $this->route('prueba')?->id;

            [$columna, $personaId] = $this->esExterno()
                ? ['persona_externa_id', PersonaExterna::where('documento', $this->input('externo_documento'))->value('id')]
                : ['colaborador_id', $this->input('colaborador_id')];

            if ($personaId && PruebaAlcoholemia::existeConflictoDeIntervalo((int) $personaId, $this->input('tipo'), $fechaHora, $ignorarId, $columna)) {
                $tipoLabel = (new PruebaAlcoholemia(['tipo' => $this->input('tipo')]))->tipoLabel();
                $quien = $this->esExterno() ? 'Esta persona' : 'Este colaborador';
                $validator->errors()->add(
                    'tipo',
                    "{$quien} ya tiene una prueba de tipo \"{$tipoLabel}\" registrada en las últimas {$horas} horas."
                );
            }
        });
    }
}
