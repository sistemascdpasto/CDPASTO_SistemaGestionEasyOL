<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreSimitConsultaRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorizacion real la hace el middleware EnsureSimitApiToken
        // sobre la ruta (token compartido, no un usuario autenticado).
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'placa' => ['required', 'string', 'max:20'],
            'timestamp' => ['required', 'date'],
            'status' => ['required', 'string', 'in:ok,captcha,sin_comparendos,error'],
            'raw_text' => ['nullable', 'string'],
            'screenshot' => ['nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
