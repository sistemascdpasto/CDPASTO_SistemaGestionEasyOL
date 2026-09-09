<?php

namespace App\Http\Requests\Seguridad;

use Illuminate\Foundation\Http\FormRequest;

class ImportarEvaluacionesOwdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'archivos' => ['required', 'array', 'min:1'],
            'archivos.*' => ['file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }
}
