<?php

namespace App\Http\Requests\Gente;

use App\Http\Requests\Gente\Concerns\ValidatesColaboradorDocumentos;
use Illuminate\Foundation\Http\FormRequest;

class StoreLlamadoAtencionRequest extends FormRequest
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
            'observacion' => ['required', 'string', 'max:2000'],
            'documento' => ['nullable', 'file', 'mimes:'.self::MIMES, 'max:5120'],
        ];
    }
}
