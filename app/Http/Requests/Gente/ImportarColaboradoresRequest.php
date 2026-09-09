<?php

namespace App\Http\Requests\Gente;

use Illuminate\Foundation\Http\FormRequest;

class ImportarColaboradoresRequest extends FormRequest
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
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:10240'],
        ];
    }
}
