<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class StoreMantenimientoRequest extends FormRequest
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
            'fecha' => ['required', 'date'],
            'descripcion' => ['required', 'string', 'max:1000'],
        ];
    }
}
