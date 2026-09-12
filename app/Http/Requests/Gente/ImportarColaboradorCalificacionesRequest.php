<?php

namespace App\Http\Requests\Gente;

use Illuminate\Foundation\Http\FormRequest;

class ImportarColaboradorCalificacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:15360'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo Excel (.xlsx, .xls) o CSV.',
            'archivo.mimes' => 'El archivo debe estar en formato Excel (.xlsx, .xls) o CSV.',
            'archivo.max' => 'El archivo no debe superar los 15 MB.',
        ];
    }
}
