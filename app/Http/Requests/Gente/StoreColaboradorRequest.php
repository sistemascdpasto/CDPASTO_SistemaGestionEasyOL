<?php

namespace App\Http\Requests\Gente;

use App\Http\Requests\Gente\Concerns\ValidatesColaboradorDocumentos;
use App\Support\Seguridad\ColaboradorDocumentoCampos;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Crea el colaborador como borrador a partir del Paso 1 del wizard
 * (HU01/HU02). Los pasos siguientes se guardan con
 * Update ColaboradorPasoNRequest sobre el registro ya creado.
 */
class StoreColaboradorRequest extends FormRequest
{
    use ValidatesColaboradorDocumentos;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', Rule::exists('users', 'id'), Rule::unique('colaboradores', 'user_id')],
            'cedula' => ['required', 'string', 'max:30', Rule::unique('colaboradores', 'cedula')],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'imagen' => ['nullable', 'image', 'max:2048'],

            'tipo_documento' => ['nullable', Rule::in([...config('seguridad.colaboradores.tipos_documento'), 'Otro'])],
            'tipo_documento_otro_label' => ['nullable', 'required_if:tipo_documento,Otro', 'string', 'max:100'],

            'expedido_en' => ['nullable', 'string', 'max:100'],
            'sexo' => ['nullable', Rule::in(['femenino', 'masculino'])],
            'fecha_nacimiento' => ['nullable', 'date'],
            'ciudad_residencia' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estrato' => ['nullable', Rule::in(['1', '2', '3', '4', '5', '6'])],
            'celular_1' => ['nullable', 'digits_between:1,10'],
            'celular_2' => ['nullable', 'digits_between:1,10'],
            'correo' => ['nullable', 'email', 'max:255'],
            'estado_civil' => ['nullable', Rule::in(['soltero', 'union_libre', 'casado', 'divorciado', 'viudo'])],

            'discapacidad' => ['nullable', Rule::in(['no_aplica', 'aplica'])],
            'discapacidad_tipo' => ['nullable', 'required_if:discapacidad,aplica', 'string', 'max:150'],
            'discapacidad_observaciones' => ['nullable', 'required_if:discapacidad,aplica', 'string', 'max:2000'],
            'victima_conflicto' => ['nullable', Rule::in(['si', 'no'])],
            'victima_conflicto_observaciones' => ['nullable', 'required_if:victima_conflicto,si', 'string', 'max:2000'],
            'libreta_militar' => ['nullable', Rule::in(['no_aplica', 'aplica'])],
            'runt_aplica' => ['nullable', Rule::in(['no_aplica', 'aplica'])],

            'eps' => ['nullable', 'string', 'max:100'],
            'eps_otro' => ['nullable', 'required_if:eps,Otro', 'string', 'max:100'],
            'afp' => ['nullable', 'string', 'max:100'],
            'afp_otro' => ['nullable', 'required_if:afp,Otro', 'string', 'max:100'],
            'arl' => ['nullable', 'string', 'max:100'],
            'arl_otro' => ['nullable', 'required_if:arl,Otro', 'string', 'max:100'],

            'sena_especialidad' => ['nullable', 'string', 'max:150'],
            'sena_numero_grupo' => ['nullable', 'integer'],
            'sena_institucion' => ['nullable', 'string', 'max:150'],
            'sena_nit' => ['nullable', 'string', 'max:30'],
            'sena_centro_formacion' => ['nullable', 'string', 'max:150'],

            'is_active' => ['boolean'],

            ...$this->documentoRules(ColaboradorDocumentoCampos::PASO1),
        ];
    }
}
