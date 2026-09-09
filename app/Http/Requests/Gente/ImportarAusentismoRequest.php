<?php

namespace App\Http\Requests\Gente;

use Illuminate\Foundation\Http\FormRequest;

class ImportarAusentismoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivo' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv,txt',
                'max:10240', // Máximo 10MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Debe seleccionar un archivo para importar.',
            'archivo.file' => 'El archivo proporcionado no es válido.',
            'archivo.mimes' => 'El archivo debe estar en formato Excel (.xlsx, .xls) o CSV (.csv).',
            'archivo.max' => 'El tamaño máximo del archivo es de 10 MB.',
        ];
    }
}
