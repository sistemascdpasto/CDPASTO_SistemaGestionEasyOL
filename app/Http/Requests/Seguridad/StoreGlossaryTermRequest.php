<?php

namespace App\Http\Requests\Seguridad;

use App\Http\Controllers\Seguridad\GlossaryTermController;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGlossaryTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $termId = $this->route('glosario')?->id;

        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('glossary_terms')
                    ->where('categoria', $this->input('categoria'))
                    ->ignore($termId)
                    ->whereNull('deleted_at'),
            ],
            'definicion' => ['required', 'string', 'min:10', 'max:5000'],
            'categoria' => ['required', 'string', Rule::in(GlossaryTermController::CATEGORIES)],
            'pregunta_numero' => ['nullable', 'string', 'max:50'],
            'representacion' => ['nullable', 'string', 'max:2000'],
            'enlaces_de_interes' => ['nullable', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del término es obligatorio.',
            'nombre.unique' => 'Ya existe un término con este nombre en la misma categoría.',
            'definicion.required' => 'La definición es obligatoria.',
            'definicion.min' => 'La definición debe tener al menos 10 caracteres.',
            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.in' => 'La categoría seleccionada no es válida.',
            'enlaces_de_interes.url' => 'El enlace de interés debe ser una URL válida.',
        ];
    }
}
