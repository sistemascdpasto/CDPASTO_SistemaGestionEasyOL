<?php

namespace App\Http\Requests\Gente;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColaboradorEntrenamientoRequest extends FormRequest
{
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
            'entrenamiento_id' => ['nullable', 'required_without:entrenamiento_nombre', Rule::exists('entrenamientos', 'id')],
            'entrenamiento_nombre' => ['nullable', 'required_without:entrenamiento_id', 'string', 'max:150'],
            'fecha_registro' => ['required', 'date'],
            'hora_registro' => ['required', 'date_format:H:i'],
        ];
    }
}
